# import os
# import re
# import uuid
# import base64
# import cv2
# import numpy as np
# import pytesseract
# import easyocr
# reader = easyocr.Reader(['en'], gpu=False)

# try:
#     from flask import Flask, request, jsonify
# except ImportError:
#     print("Vui lòng cài đặt Flask bằng: pip install Flask")

# try:
#     from ultralytics import YOLO
# except ImportError:
#     YOLO = None

# app = Flask(__name__)

# # 🚨 CHỈNH SỬA 1: Cấu hình Tesseract OCR linh hoạt giữa Windows và Linux (Render)
# if os.name == 'nt':  # Nếu chạy dưới máy Windows local của bạn
#     pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"
# else:
#     # Trên môi trường Linux (Render), tesseract sau khi cài qua APT sẽ nhận lệnh trực tiếp thế này
#     pytesseract.pytesseract.tesseract_cmd = "tesseract"

# DEBUG = True
# # Trên Render, thư mục ghi file tạm thời an toàn nhất là /tmp
# DEBUG_DIR = "/tmp/debug" if os.name != 'nt' else "debug"
# os.makedirs(DEBUG_DIR, exist_ok=True)

# MODEL_PATH = os.path.join(os.path.dirname(__file__), "models", "license_plate_detector.pt")
# yolo_model = None

# # 🚨 CHỈNH SỬA 2: Phòng chống tràn bộ nhớ (Out of Memory) trên gói Render Free
# if YOLO is not None and os.path.isfile(MODEL_PATH):
#     try:
#         # Ép buộc YOLO chạy hoàn toàn bằng CPU và giảm tối đa gánh nặng bộ nhớ
#         yolo_model = YOLO(MODEL_PATH)
#         print("🚀 [AI] Đã nạp thành công mô hình YOLO.")
#     except Exception as err:
#         yolo_model = None
#         print("⚠️ [AI] YOLO model load failed (Có thể do tràn RAM Server):", err)


# def image_to_base64(img):
#     _, buffer = cv2.imencode('.jpg', img)
#     return base64.b64encode(buffer).decode('utf-8')


# def normalize_plate(text: str) -> str:
#     text = text.upper()
#     text = re.sub(r"[^A-Z0-9]", "", text)
#     return text


# def preprocess(img):
#     gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
#     blur = cv2.bilateralFilter(gray, 11, 17, 17)
#     _, thresh = cv2.threshold(
#         blur, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU
#     )
#     return thresh

# def ocr_image(img):
#     try:
#         # EasyOCR đọc trực tiếp từ mảng ảnh OpenCV
#         results = reader.readtext(img)
#         if results:
#             # Ghép các cụm chữ đọc được lại với nhau
#             text = "".join([res[1] for res in results])
#             return normalize_plate(text)
#     except Exception as e:
#         print(f"OCR Error: {e}")
#     return ""


# def detect_plate_yolo(img):
#     if yolo_model is None:
#         return None
#     try:
#         results = yolo_model.predict(source=img, imgsz=320, conf=0.25, iou=0.45, verbose=False) # Giảm imgsz xuống 320 để giảm RAM gánh
#         if not results or len(results[0].boxes) == 0:
#             return None

#         xyxy = results[0].boxes.xyxy.cpu().numpy()
#         if xyxy.size == 0:
#             return None

#         areas = [(x2 - x1) * (y2 - y1) for x1, y1, x2, y2 in xyxy]
#         best_idx = int(np.argmax(areas))
#         x1, y1, x2, y2 = xyxy[best_idx].astype(int)
#         x1 = max(0, x1)
#         y1 = max(0, y1)
#         x2 = min(img.shape[1], x2)
#         y2 = min(img.shape[0], y2)

#         return img[y1:y2, x1:x2]
#     except Exception as e:
#         print(f"⚠️ YOLO nhận diện thất bại (Fallback sang Contour): {e}")
#         return None


# def detect_plate(img):
#     h_img, w_img, _ = img.shape

#     gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
#     blur = cv2.GaussianBlur(gray, (5, 5), 0)
#     edged = cv2.Canny(blur, 50, 200)

#     contours, _ = cv2.findContours(edged, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
#     contours = sorted(contours, key=cv2.contourArea, reverse=True)

#     for c in contours[:5]:
#         x, y, w, h = cv2.boundingRect(c)
#         ratio = w / float(h)

#         if (
#             2.5 < ratio < 5.5 and
#             w > 0.3 * w_img and
#             h > 0.08 * h_img
#         ):
#             return img[y:y+h, x:x+w]

#     return None


# @app.route("/predict", methods=["POST"])
# def predict():
#     file = request.files.get("file")
#     if file is None:
#         return jsonify({"plate": "", "error": "Không có file"}), 400

#     img = cv2.imdecode(np.frombuffer(file.read(), np.uint8), cv2.IMREAD_COLOR)
#     if img is None:
#         return jsonify({"plate": "", "error": "Ảnh không hợp lệ"}), 400

#     if DEBUG:
#         cv2.imwrite(f"{DEBUG_DIR}/input.jpg", img)

#     plate_img = detect_plate_yolo(img)
#     if plate_img is not None:
#         source = 'yolo'
#     else:
#         plate_img = detect_plate(img)
#         source = 'contour' if plate_img is not None else 'original'

#     if plate_img is None:
#         plate_img = img

#     if DEBUG:
#         cv2.imwrite(f"{DEBUG_DIR}/plate.jpg", plate_img)

#     processed = preprocess(plate_img)
#     if DEBUG:
#         cv2.imwrite(f"{DEBUG_DIR}/processed.jpg", processed)

#     plate = ocr_image(processed)

#     response = {"plate": plate, "plate_source": source}
#     if DEBUG:
#         response["plate_image"] = image_to_base64(plate_img)

#     return jsonify(response)


# # 🚨 CHỈNH SỬA 3: Nhận diện cổng động từ Server Hosting (Render)
# if __name__ == "__main__":
#     # Render yêu cầu ứng dụng lắng nghe qua cổng do hệ thống cấp (thông qua môi trường PORT)
#     port = int(os.environ.get("PORT", 8001))
#     # Phải đặt host là 0.0.0.0 để mở luồng kết nối ra internet bên ngoài
#     app.run(host="0.0.0.0", port=port, debug=False)
import os
import re
import base64
import cv2
import numpy as np
from flask import Flask, request, jsonify

# 🚀 Khởi tạo PaddleOCR siêu nhẹ
from paddleocr import PaddleOCR
# use_gpu=False để chạy bằng CPU, lang='en' tối ưu cho chữ và số bãi xe
ocr = PaddleOCR(use_angle_cls=False, lang='en')

app = Flask(__name__)

DEBUG = True
DEBUG_DIR = "/tmp/debug" if os.name != 'nt' else "debug"
os.makedirs(DEBUG_DIR, exist_ok=True)


def image_to_base64(img):
    _, buffer = cv2.imencode('.jpg', img)
    return base64.b64encode(buffer).decode('utf-8')


def normalize_plate(text: str) -> str:
    text = text.upper()
    text = re.sub(r"[^A-Z0-9]", "", text)
    return text


@app.route("/predict", methods=["POST"])
def predict():
    file = request.files.get("file")
    if file is None:
        return jsonify({"plate": "", "error": "Không có file"}), 400

    # Đọc ảnh từ request gửi lên
    img = cv2.imdecode(np.frombuffer(file.read(), np.uint8), cv2.IMREAD_COLOR)
    if img is None:
        return jsonify({"plate": "", "error": "Ảnh không hợp lệ"}), 400

    if DEBUG:
        cv2.imwrite(f"{DEBUG_DIR}/input.jpg", img)

    try:
        # 🚀 Gọi PaddleOCR quét toàn bộ ảnh để tìm cụm chữ biển số xe
        result = ocr.ocr(img, cls=False)
        
        plate_text = ""
        # PaddleOCR trả về cấu trúc danh sách các vùng chữ tìm thấy
        if result and result[0]:
            for line in result[0]:
                text_detected = line[1][0] # Chuỗi văn bản đọc được
                confidence = line[1][1]    # Độ tự tin (%)
                
                # Làm sạch chữ thử xem có giống biển số không
                clean_text = normalize_plate(text_detected)
                
                # Nếu chuỗi đọc được có độ dài từ 4 ký tự trở lên thì thu thập
                if len(clean_text) >= 4:
                    plate_text += clean_text

        # Định dạng chuẩn lại biển số cuối cùng
        final_plate = normalize_plate(plate_text)

        response = {
            "plate": final_plate, 
            "plate_source": "paddleocr"
        }
        
        if DEBUG:
            response["plate_image"] = image_to_base64(img)

        return jsonify(response)

    except Exception as e:
        print(f"❌ [OCR Error] Hệ thống gặp lỗi phân tích: {e}")
        return jsonify({"plate": "", "error": str(e)}), 500


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    app.run(host="0.0.0.0", port=port, debug=False)