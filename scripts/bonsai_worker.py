import argparse
import json
import os
import time
import urllib.error
import urllib.request
from datetime import datetime, timedelta, timezone
from pathlib import Path

import joblib
import mysql.connector
import numpy as np
import tensorflow as tf


BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent
MODEL_DIR = BASE_DIR / "model"
MODEL_PATH = MODEL_DIR / "model_bonsai_lstm.keras"
SCALER_PATH = MODEL_DIR / "scaler_bonsai.pkl"
LABEL_INFO_PATH = MODEL_DIR / "label_info.json"
FIREBASE_DATABASE_URL = "https://skripsi1-7a46f-default-rtdb.asia-southeast1.firebasedatabase.app"
WIB = timezone(timedelta(hours=7))
DEFAULT_SYNC_INTERVAL_SECONDS = 240
DEFAULT_MIN_PREDICTION_READINGS = 12


def now_wib() -> datetime:
    return datetime.now(WIB).replace(tzinfo=None)


def read_env(path: Path) -> dict:
    env = {}
    if not path.exists():
        return env

    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        env[key.strip()] = value.strip().strip('"').strip("'")
    return env


def mysql_config() -> dict:
    env = read_env(PROJECT_DIR / ".env")
    return {
        "host": env.get("DB_HOST", "127.0.0.1"),
        "port": int(env.get("DB_PORT", 3306)),
        "database": env.get("DB_DATABASE", "laravel"),
        "user": env.get("DB_USERNAME", "root"),
        "password": env.get("DB_PASSWORD", ""),
        "autocommit": False,
    }


def firebase_url(path: str) -> str:
    return f"{FIREBASE_DATABASE_URL.rstrip('/')}/{path.strip('/')}.json"


def firebase_get(path: str):
    request = urllib.request.Request(firebase_url(path), method="GET")
    with urllib.request.urlopen(request, timeout=15) as response:
        payload = response.read().decode("utf-8")
    return json.loads(payload) if payload else None


def firebase_put(path: str, value) -> None:
    body = json.dumps(value).encode("utf-8")
    request = urllib.request.Request(
        firebase_url(path),
        data=body,
        headers={"Content-Type": "application/json"},
        method="PUT",
    )
    with urllib.request.urlopen(request, timeout=15) as response:
        response.read()


def ensure_tables(connection) -> None:
    cursor = connection.cursor()
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS sensor_readings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sensor_timestamp TIMESTAMP NULL,
            temperature_c DECIMAL(8, 3) NOT NULL,
            humidity_air_pct DECIMAL(8, 3) NOT NULL,
            soil_moisture_pct DECIMAL(8, 3) NOT NULL,
            firebase_waktu VARCHAR(255) NULL,
            raw_payload JSON NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX sensor_readings_sensor_timestamp_index (sensor_timestamp)
        )
        """
    )
    cursor.execute(
        """
        CREATE TABLE IF NOT EXISTS prediction_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sensor_reading_id BIGINT UNSIGNED NULL,
            prediction_timestamp TIMESTAMP NULL,
            predicted_soil_moisture_pct DECIMAL(8, 3) NULL,
            prediction_probability DECIMAL(8, 5) NULL,
            prediction_class VARCHAR(255) NULL,
            pump_status VARCHAR(255) NULL,
            irrigation_reason VARCHAR(255) NULL,
            raw_output JSON NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            INDEX prediction_logs_prediction_timestamp_index (prediction_timestamp)
        )
        """
    )
    connection.commit()
    cursor.close()


def parse_sensor_timestamp(value) -> datetime:
    if value:
        for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%dT%H:%M:%S", "%Y-%m-%dT%H:%M:%S.%fZ"):
            try:
                return datetime.strptime(str(value), fmt)
            except ValueError:
                continue
    return now_wib()


def read_bonsai_sensor() -> dict:
    payload = firebase_get("bonsai")
    if not isinstance(payload, dict):
        raise RuntimeError("Payload Firebase path 'bonsai' kosong atau bukan object.")

    try:
        return {
            "temperature_c": float(payload["suhu"]),
            "humidity_air_pct": float(payload["kelembapan_udara"]),
            "soil_moisture_pct": float(payload["kelembapan_tanah"]),
            "firebase_waktu": payload.get("waktu"),
            "raw_payload": payload,
        }
    except KeyError as exc:
        raise RuntimeError(f"Kolom Firebase bonsai tidak lengkap: {exc}") from exc


def insert_sensor_reading(connection, sensor: dict) -> int:
    now = now_wib()
    sensor_timestamp = parse_sensor_timestamp(sensor.get("firebase_waktu"))
    cursor = connection.cursor()
    cursor.execute(
        """
        INSERT INTO sensor_readings (
            sensor_timestamp, temperature_c, humidity_air_pct, soil_moisture_pct,
            firebase_waktu, raw_payload, created_at, updated_at
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            sensor_timestamp,
            sensor["temperature_c"],
            sensor["humidity_air_pct"],
            sensor["soil_moisture_pct"],
            sensor.get("firebase_waktu"),
            json.dumps(sensor["raw_payload"]),
            now,
            now,
        ),
    )
    connection.commit()
    inserted_id = cursor.lastrowid
    cursor.close()
    return inserted_id


def latest_sequence(connection, look_back: int, features: list[str]) -> tuple[list[int], np.ndarray]:
    cursor = connection.cursor(dictionary=True)
    cursor.execute(
        """
        SELECT id, temperature_c, humidity_air_pct, soil_moisture_pct
        FROM sensor_readings
        ORDER BY sensor_timestamp DESC, id DESC
        LIMIT %s
        """,
        (look_back,),
    )
    rows = cursor.fetchall()
    cursor.close()
    rows.reverse()

    ids = [int(row["id"]) for row in rows]
    values = np.array([[float(row[feature]) for feature in features] for row in rows], dtype=np.float32)
    return ids, values


def irrigation_reason(
    temp_c: float,
    humidity_air: float,
    soil_actual: float,
    predicted_soil: float,
    label_info: dict,
) -> tuple[str, str]:
    soil_threshold = float(label_info.get("soil_threshold", 60.0))
    saturated_threshold = float(label_info.get("soil_saturated_threshold", 80.0))
    temp_high_threshold = float(label_info.get("temp_high_threshold", 30.0))
    humidity_low_threshold = float(label_info.get("humidity_low_threshold", 50.0))

    sensor_ok = (
        10 <= temp_c <= 45
        and 0 < humidity_air <= 100
        and 0 <= soil_actual <= 100
        and 0 <= predicted_soil <= 100
    )
    if not sensor_ok:
        return "off", "sensor_error_off"
    if soil_actual > saturated_threshold:
        return "off", "overwatering_guard_off"
    if temp_c > temp_high_threshold and humidity_air < humidity_low_threshold and predicted_soil < soil_threshold:
        return "on", "early_prevention_on"
    if soil_actual < soil_threshold:
        return "on", "actual_soil_dry_on"
    if predicted_soil < soil_threshold:
        return "on", "predicted_soil_dry_on"
    return "off", "normal_off"


def load_model_bundle():
    missing = [path for path in (MODEL_PATH, SCALER_PATH, LABEL_INFO_PATH) if not path.exists()]
    if missing:
        raise RuntimeError(f"Artefak model tidak ditemukan: {missing}")

    try:
        model = tf.keras.models.load_model(MODEL_PATH, compile=False, safe_mode=False)
    except TypeError:
        model = tf.keras.models.load_model(MODEL_PATH, compile=False)
    scaler = joblib.load(SCALER_PATH)
    label_info = json.loads(LABEL_INFO_PATH.read_text(encoding="utf-8"))
    return model, scaler, label_info


def pad_sequence(sequence: np.ndarray, target_length: int) -> np.ndarray:
    if len(sequence) >= target_length:
        return sequence[-target_length:]

    pad_count = target_length - len(sequence)
    first_row = sequence[0:1]
    padding = np.repeat(first_row, pad_count, axis=0)
    return np.vstack([padding, sequence])


def predict_and_update(connection, model, scaler, label_info: dict, min_prediction_readings: int) -> None:
    look_back = int(label_info.get("look_back", 24))
    features = label_info.get("features", ["temperature_c", "humidity_air_pct", "soil_moisture_pct"])
    ids, sequence = latest_sequence(connection, look_back, features)
    available_readings = len(sequence)
    min_required = min(max(1, min_prediction_readings), look_back)

    if available_readings < min_required:
        firebase_put(
            "prediksi",
            {
                "status": "menunggu_data",
                "message": f"Menunggu minimal {min_required} data sensor di MySQL.",
                "jumlah_data": available_readings,
                "minimal_data": min_required,
                "waktu": now_wib().strftime("%Y-%m-%d %H:%M:%S"),
            },
        )
        print(f"[PREDICT] Menunggu data: {available_readings}/{min_required}")
        return

    sequence = pad_sequence(sequence, look_back)
    scaled = scaler.transform(sequence)
    x_input = scaled.reshape(1, look_back, len(features))
    prediction = model.predict(x_input, verbose=0)

    model_probability = float(np.ravel(prediction[0])[0])
    predicted_soil = float(np.clip(np.ravel(prediction[1])[0] * 100.0, 0.0, 100.0))
    probability = float(np.clip(1.0 - (predicted_soil / 100.0), 0.0, 1.0))

    latest = sequence[-1]
    pump_status, reason = irrigation_reason(
        temp_c=float(latest[0]),
        humidity_air=float(latest[1]),
        soil_actual=float(latest[2]),
        predicted_soil=predicted_soil,
        label_info=label_info,
    )
    prediction_class = "Siram" if pump_status == "on" else "Tidak Siram"
    now = now_wib()
    raw_output = {
        "model_probability": model_probability,
        "operational_probability": probability,
        "temperature_actual_c": float(latest[0]),
        "humidity_air_actual_pct": float(latest[1]),
        "soil_moisture_actual_pct": float(latest[2]),
        "available_readings": available_readings,
        "min_prediction_readings": min_required,
        "model_look_back": look_back,
        "sequence_padded": available_readings < look_back,
    }

    cursor = connection.cursor()
    cursor.execute(
        """
        INSERT INTO prediction_logs (
            sensor_reading_id, prediction_timestamp, predicted_soil_moisture_pct,
            prediction_probability, prediction_class, pump_status, irrigation_reason,
            raw_output, created_at, updated_at
        ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
        """,
        (
            ids[-1],
            now,
            predicted_soil,
            probability,
            prediction_class,
            pump_status,
            reason,
            json.dumps(raw_output),
            now,
            now,
        ),
    )
    connection.commit()
    cursor.close()

    firebase_put("Pompa/pompa", pump_status)
    firebase_put(
        "prediksi",
        {
            "status": prediction_class,
            "pompa": pump_status,
            "predicted_soil_moisture_pct": round(predicted_soil, 3),
            "prediction_probability": round(probability, 5),
            "irrigation_reason": reason,
            "jumlah_data": available_readings,
            "minimal_data": min_required,
            "model_look_back": look_back,
            "sequence_padded": available_readings < look_back,
            "waktu": now.strftime("%Y-%m-%d %H:%M:%S"),
        },
    )
    print(f"[PREDICT] {prediction_class} | pompa={pump_status} | pred_soil={predicted_soil:.2f}% | reason={reason}")


def run_loop(interval: int, once: bool, min_prediction_readings: int) -> None:
    model, scaler, label_info = load_model_bundle()
    connection = mysql.connector.connect(**mysql_config())
    ensure_tables(connection)

    try:
        while True:
            try:
                sensor = read_bonsai_sensor()
                reading_id = insert_sensor_reading(connection, sensor)
                print(
                    "[SYNC] "
                    f"reading_id={reading_id} "
                    f"suhu={sensor['temperature_c']:.2f} "
                    f"udara={sensor['humidity_air_pct']:.2f} "
                    f"tanah={sensor['soil_moisture_pct']:.2f}"
                )
                predict_and_update(connection, model, scaler, label_info, min_prediction_readings)
            except (mysql.connector.Error, urllib.error.URLError, RuntimeError, ValueError) as exc:
                print(f"[ERROR] {exc}")

            if once:
                break
            time.sleep(interval)
    finally:
        connection.close()


def main() -> None:
    parser = argparse.ArgumentParser(description="Sinkronisasi Firebase, MySQL, dan prediksi LSTM Bonsai.")
    parser.add_argument(
        "--interval",
        type=int,
        default=DEFAULT_SYNC_INTERVAL_SECONDS,
        help="Interval polling Firebase dan simpan MySQL dalam detik.",
    )
    parser.add_argument(
        "--min-prediction-readings",
        type=int,
        default=DEFAULT_MIN_PREDICTION_READINGS,
        help="Jumlah minimal data MySQL sebelum model mulai memprediksi.",
    )
    parser.add_argument("--once", action="store_true", help="Jalankan satu siklus lalu berhenti.")
    args = parser.parse_args()

    run_loop(
        interval=max(1, args.interval),
        once=args.once,
        min_prediction_readings=max(1, args.min_prediction_readings),
    )


if __name__ == "__main__":
    main()
