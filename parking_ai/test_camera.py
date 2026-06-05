import cv2
import requests
import sys
import os

DEBUG_DIR = "debug"
os.makedirs(DEBUG_DIR, exist_ok=True)

def capture_and_send(cam_index=0, url='http://127.0.0.1:8001/predict'):
    cap = cv2.VideoCapture(cam_index)
    if not cap.isOpened():
        print(f"Không thể mở camera index={cam_index}")
        return 1

    ret, frame = cap.read()
    cap.release()

    if not ret:
        print("Không đọc được frame từ camera")
        return 1

    # Lưu ảnh kiểm tra
    path = os.path.join(DEBUG_DIR, 'capture.jpg')
    cv2.imwrite(path, frame)
    print(f"Ảnh đã lưu: {path}")

    # Gửi tới endpoint
    _, img_encoded = cv2.imencode('.jpg', frame)
    files = {'file': ('capture.jpg', img_encoded.tobytes(), 'image/jpeg')}

    try:
        resp = requests.post(url, files=files, timeout=10)
        print('HTTP', resp.status_code)
        print(resp.text)
    except Exception as e:
        print('Lỗi khi gửi ảnh:', e)
        return 1

    return 0

if __name__ == '__main__':
    cam = int(sys.argv[1]) if len(sys.argv) > 1 else 0
    sys.exit(capture_and_send(cam))
