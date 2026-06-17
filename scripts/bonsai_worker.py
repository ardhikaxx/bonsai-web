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

# ── KONFIGURASI PATH ──────────────────────────────────────────────────
BASE_DIR = Path(__file__).resolve().parent
PROJECT_DIR = BASE_DIR.parent
MODEL_DIR = BASE_DIR / "model"

# Artefak LSTM Ringan (Manual)
WEIGHTS_PATH = MODEL_DIR / "lstm_weights.npz"
SCALER_PATH = MODEL_DIR / "scaler_bonsai.pkl"
LABEL_INFO_PATH = MODEL_DIR / "label_info.json"

FIREBASE_DATABASE_URL = "https://skripsi1-7a46f-default-rtdb.asia-southeast1.firebasedatabase.app"
WIB = timezone(timedelta(hours=7))
DEFAULT_SYNC_INTERVAL_SECONDS = 240
DEFAULT_MIN_PREDICTION_READINGS = 12

# ── ALGORITMA LSTM MANUAL (MATEMATIKA ASLI) ───────────────────────────
def sigmoid(x):
    return 1 / (1 + np.exp(-x))

def tanh(x):
    return np.tanh(x)

def manual_lstm_inference(x_seq, weights):
    """
    Inference LSTM murni menggunakan matriks NumPy.
    Implementasi dari algoritma LSTM:
    i_gate, f_gate, o_gate, dan cell_state.
    """
    W = weights['lstm_W']
    U = weights['lstm_U']
    b = weights['lstm_b']
    units = U.shape[0]
    
    h = np.zeros(units)
    c = np.zeros(units)
    
    # Loop Timesteps (Urutan waktu sensor)
    for x_t in x_seq:
        # Gabungkan input dan hidden state
        z = np.dot(x_t, W) + np.dot(h, U) + b
        
        # Pisahkan hasil ke 4 gate LSTM
        i = sigmoid(z[0:units])          # Input Gate
        f = sigmoid(z[units:units*2])    # Forget Gate
        g = tanh(z[units*2:units*3])     # Candidate
        o = sigmoid(z[units*3:units*4])  # Output Gate
        
        # Update Cell State dan Hidden State
        c = f * c + i * g
        h = o * tanh(c)
    
    # Layer Output (Dense)
    y_cls = sigmoid(np.dot(h, weights['dense_cls_W']) + weights['dense_cls_b'])[0]
    y_reg = (np.dot(h, weights['dense_reg_W']) + weights['dense_reg_b'])[0]
    
    return float(y_cls), float(y_reg * 100.0)

# ── FUNGSI PENDUKUNG ──────────────────────────────────────────────────
def now_wib() -> datetime:
    return datetime.now(WIB).replace(tzinfo=None)

def mysql_config() -> dict:
    from pathlib import Path
    def read_env(path: Path) -> dict:
        env = {}
        if not path.exists(): return env
        for line in path.read_text(encoding="utf-8").splitlines():
            line = line.strip()
            if not line or line.startswith("#") or "=" not in line: continue
            key, value = line.split("=", 1)
            env[key.strip()] = value.strip().strip('"').strip("'")
        return env
    
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

def firebase_get(path: str, retries=3):
    for i in range(retries):
        try:
            request = urllib.request.Request(firebase_url(path), method="GET")
            with urllib.request.urlopen(request, timeout=20) as response:
                payload = response.read().decode("utf-8")
            return json.loads(payload) if payload else None
        except (urllib.error.URLError, TimeoutError) as e:
            if i == retries - 1: raise
            print(f"[RETRY] Firebase GET failed: {e}. Retrying {i+1}/{retries}...")
            time.sleep(2)

def firebase_put(path: str, value, retries=3) -> None:
    body = json.dumps(value).encode("utf-8")
    for i in range(retries):
        try:
            request = urllib.request.Request(firebase_url(path), data=body, headers={"Content-Type": "application/json"}, method="PUT")
            with urllib.request.urlopen(request, timeout=20) as response: response.read()
            return
        except (urllib.error.URLError, TimeoutError) as e:
            if i == retries - 1: raise
            print(f"[RETRY] Firebase PUT failed: {e}. Retrying {i+1}/{retries}...")
            time.sleep(2)

def ensure_tables(connection) -> None:
    cursor = connection.cursor()
    cursor.execute("CREATE TABLE IF NOT EXISTS sensor_readings (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sensor_timestamp TIMESTAMP NULL, temperature_c DECIMAL(8, 3) NOT NULL, humidity_air_pct DECIMAL(8, 3) NOT NULL, soil_moisture_pct DECIMAL(8, 3) NOT NULL, firebase_waktu VARCHAR(255) NULL, raw_payload JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX sensor_readings_sensor_timestamp_index (sensor_timestamp))")
    cursor.execute("CREATE TABLE IF NOT EXISTS prediction_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, sensor_reading_id BIGINT UNSIGNED NULL, prediction_timestamp TIMESTAMP NULL, predicted_soil_moisture_pct DECIMAL(8, 3) NULL, prediction_probability DECIMAL(8, 5) NULL, prediction_class VARCHAR(255) NULL, pump_status VARCHAR(255) NULL, irrigation_reason VARCHAR(255) NULL, raw_output JSON NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, INDEX prediction_logs_prediction_timestamp_index (prediction_timestamp))")
    connection.commit()
    cursor.close()

def parse_sensor_timestamp(value) -> datetime:
    if value:
        for fmt in ("%Y-%m-%d %H:%M:%S", "%Y-%m-%dT%H:%M:%S", "%Y-%m-%dT%H:%M:%S.%fZ"):
            try: return datetime.strptime(str(value), fmt)
            except ValueError: continue
    return now_wib()

def read_bonsai_sensor() -> dict:
    payload = firebase_get("bonsai")
    if not isinstance(payload, dict): raise RuntimeError("Payload Firebase 'bonsai' kosong.")
    try:
        return {"temperature_c": float(payload["suhu"]), "humidity_air_pct": float(payload["kelembapan_udara"]), "soil_moisture_pct": float(payload["kelembapan_tanah"]), "firebase_waktu": payload.get("waktu"), "raw_payload": payload}
    except KeyError as exc: raise RuntimeError(f"Kolom Firebase tidak lengkap: {exc}")

def insert_sensor_reading(connection, sensor: dict) -> int:
    now = now_wib()
    cursor = connection.cursor()
    cursor.execute("INSERT INTO sensor_readings (sensor_timestamp, temperature_c, humidity_air_pct, soil_moisture_pct, firebase_waktu, raw_payload, created_at, updated_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
        (parse_sensor_timestamp(sensor.get("firebase_waktu")), sensor["temperature_c"], sensor["humidity_air_pct"], sensor["soil_moisture_pct"], sensor.get("firebase_waktu"), json.dumps(sensor["raw_payload"]), now, now))
    connection.commit()
    inserted_id = cursor.lastrowid
    cursor.close()
    print(f"[INFO] Data sensor tersimpan (ID: {inserted_id}) | Waktu: {now.strftime('%H:%M:%S')}")
    return inserted_id

def latest_sequence(connection, look_back: int, features: list[str]):
    cursor = connection.cursor(dictionary=True)
    cursor.execute("SELECT id, temperature_c, humidity_air_pct, soil_moisture_pct FROM sensor_readings ORDER BY sensor_timestamp DESC, id DESC LIMIT %s", (look_back,))
    rows = cursor.fetchall(); cursor.close(); rows.reverse()
    if not rows: return [], np.empty((0, len(features)))
    ids = [int(row["id"]) for row in rows]
    values = np.array([[float(row[f]) for f in features] for row in rows], dtype=np.float32)
    return ids, values

def irrigation_reason(temp_c, humidity_air, soil_actual, predicted_soil, label_info):
    soil_threshold = float(label_info.get("soil_threshold", 60.0))
    saturated_threshold = float(label_info.get("soil_saturated_threshold", 80.0))
    temp_high_threshold = float(label_info.get("temp_high_threshold", 30.0))
    hum_low_threshold = float(label_info.get("hum_low_threshold", 50.0))

    # 1. PERTAHANAN UTAMA: Cegah Overwatering jika kelembapan aktual > 80%
    if soil_actual > saturated_threshold:
        return "off", "overwatering_guard_off"

    # 2. KONDISI UTAMA: Tanah aktual sudah kering
    if soil_actual < soil_threshold:
        return "on", "actual_soil_dry_on"

    # 3. KONDISI PREDIKSI: AI memprediksi tanah akan kering
    if predicted_soil < soil_threshold:
        # Tambahan: Cek Suhu Tinggi & Kelembapan Rendah untuk penyiraman dini
        if temp_c > temp_high_threshold and humidity_air < hum_low_threshold:
            return "on", "early_watering_hot_dry_on"
        
        return "on", "predicted_soil_dry_on"

    return "off", "normal_off"

def pad_sequence(seq, target_len):
    if len(seq) >= target_len: return seq[-target_len:]
    if len(seq) == 0: return np.zeros((target_len, 3))
    return np.vstack([np.repeat(seq[0:1], target_len - len(seq), axis=0), seq])

def load_model_bundle():
    if not all(p.exists() for p in (WEIGHTS_PATH, SCALER_PATH, LABEL_INFO_PATH)):
        raise RuntimeError(f"Artefak model tidak lengkap di {MODEL_DIR}")
    return np.load(WEIGHTS_PATH), joblib.load(SCALER_PATH), json.loads(LABEL_INFO_PATH.read_text())

def predict_and_update(connection, weights, scaler, label_info, min_readings):
    look_back = int(label_info.get("look_back", 24))
    features = label_info.get("features", ["temperature_c", "humidity_air_pct", "soil_moisture_pct"])
    ids, sequence = latest_sequence(connection, look_back, features)
    
    if len(sequence) < min_readings:
        firebase_put("prediksi", {"status": "menunggu_data", "jumlah_data": len(sequence), "waktu": now_wib().strftime("%Y-%m-%d %H:%M:%S")})
        return

    # Preprocessing & Manual LSTM Inference
    sequence_padded = pad_sequence(sequence, look_back)
    scaled = scaler.transform(sequence_padded)
    prob, predicted_soil = manual_lstm_inference(scaled, weights)
    
    latest = sequence_padded[-1]
    pump_status, reason = irrigation_reason(latest[0], latest[1], latest[2], predicted_soil, label_info)
    prediction_class = "Siram" if pump_status == "on" else "Tidak Siram"
    now = now_wib()

    # Simpan ke Database
    cursor = connection.cursor()
    cursor.execute("INSERT INTO prediction_logs (sensor_reading_id, prediction_timestamp, predicted_soil_moisture_pct, prediction_probability, prediction_class, pump_status, irrigation_reason, created_at, updated_at) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)",
        (ids[-1] if ids else None, now, predicted_soil, prob, prediction_class, pump_status, reason, now, now))
    connection.commit(); cursor.close()

    # Sinkronisasi Firebase
    firebase_put("Pompa/pompa", pump_status)
    firebase_put("prediksi", {"status": prediction_class, "pompa": pump_status, "predicted_soil_moisture_pct": round(predicted_soil, 2), "engine": "Manual LSTM (Lightweight)", "waktu": now.strftime("%Y-%m-%d %H:%M:%S")})
    print(f"[PREDICT] {prediction_class} | pred_soil={predicted_soil:.2f}% | engine=LSTM Asli")

def run_loop(interval, once, min_readings):
    weights, scaler, label_info = load_model_bundle()
    connection = mysql.connector.connect(**mysql_config())
    ensure_tables(connection)
    try:
        while True:
            try:
                # Cek apakah sistem monitoring aktif
                system_status = firebase_get("Pompa/system_active")
                print(f"[DEBUG] Firebase system_active: '{system_status}' | Waktu: {now_wib().strftime('%H:%M:%S')}")
                
                if str(system_status).lower() != "on":
                    print(f"[IDLE] Sistem Monitoring OFF. Menunggu {interval} detik...")
                else:
                    print(f"[PROCESS] Sistem Monitoring ON. Mengambil data sensor...")
                    sensor = read_bonsai_sensor()
                    reading_id = insert_sensor_reading(connection, sensor)
                    predict_and_update(connection, weights, scaler, label_info, min_readings)
                    print(f"[SUCCESS] Data diproses. Menunggu {interval} detik...")
            except Exception as e: print(f"[ERROR] {e}")
            if once: break
            time.sleep(interval)
    finally: connection.close()

def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--interval", type=int, default=DEFAULT_SYNC_INTERVAL_SECONDS)
    parser.add_argument("--min-prediction-readings", type=int, default=DEFAULT_MIN_PREDICTION_READINGS)
    parser.add_argument("--once", action="store_true")
    args = parser.parse_args()
    run_loop(args.interval, args.once, args.min_prediction_readings)

if __name__ == "__main__": main()
