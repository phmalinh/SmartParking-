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
import requests
import cv2
import numpy as np
from flask import Flask, request, jsonify

app = Flask(__name__)

# 🚀 API Key OCR Space của bạn
OCR_SPACE_API_KEY = "K85568556588957"


def normalize_plate(text: str) -> str:
    if not text:
        return ""
    text = text.upper()
    # Chỉ giữ lại chữ cái và chữ số
    text = re.sub(r"[^A-Z0-9]", "", text)
    return text


def detect_and_crop_plate(img):
    """
    Sử dụng thuật toán hình học OpenCV để tìm và cắt khung biển số xe (Tốn ít RAM)
    """
    try:
        h_img, w_img, _ = img.shape

        # 1. Chuyển ảnh về màu xám và làm mịn để giảm nhiễu
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        blur = cv2.GaussianBlur(gray, (5, 5), 0)
        
        # 2. Phát hiện các cạnh biên (Canny Edge Detection)
        edged = cv2.Canny(blur, 50, 200)

        # 3. Tìm các đường viền (Contours) trong ảnh
        contours, _ = cv2.findContours(edged, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        contours = sorted(contours, key=cv2.contourArea, reverse=True)

        # 4. Quét qua các đường viền lớn nhất xem cái nào có tỷ lệ giống biển số xe
        for c in contours[:10]:  # Xét top 10 vùng lớn nhất
            x, y, w, h = cv2.boundingRect(c)
            ratio = w / float(h)

            # Biển số xe thường là hình chữ nhật nằm ngang (Tỷ lệ rộng/cao từ 2.0 đến 5.5)
            if 2.0 < ratio < 5.5 and w > 0.15 * w_img and h > 0.05 * h_img:
                # Cắt lấy vùng ảnh chứa biển số xe
                cropped_plate = img[y:y+h, x:x+w]
                print("🎯 [OpenCV] Đã tìm thấy và cắt vùng biển số thành công.")
                return cropped_plate
    except Exception as e:
        print(f"⚠️ Không cắt được ảnh bằng OpenCV (Dùng ảnh gốc làm Fallback): {e}")
        
    return img  # Trả về ảnh gốc nếu không tìm thấy khung hình phù hợp


@app.route("/predict", methods=["POST"])
def predict():
    file = request.files.get("file")
    if file is None:
        return jsonify({"plate": "", "error": "Không có file"}), 400

    try:
        # Đọc dữ liệu ảnh từ request gửi lên
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None:
            return jsonify({"plate": "", "error": "Ảnh không hợp lệ"}), 400

        # 🚀 BƯỚC CẮT ẢNH: Tự động định vị và cắt lấy mỗi cái biển số xe trước khi gửi đi
        processed_img = detect_and_crop_plate(img)

        # Chuyển đổi ảnh đã cắt thành dạng byte để gửi qua API HTTP
        _, buffer = cv2.imencode('.jpg', processed_img)
        cropped_bytes = buffer.tobytes()
        
        # Cấu hình tham số gửi tới OCR Space
        payload = {
            "apikey": OCR_SPACE_API_KEY,
            "language": "eng",
            "isOverlayRequired": False,
            "OCREngine": "2",  # Engine 2 cực mạnh cho văn bản ngắn/biển số
        }
        
        files = {
            "file": ("cropped_plate.jpg", cropped_bytes, "image/jpeg")
        }
        
        # Gửi vùng ảnh nhỏ đã cắt sắc nét qua Cloud OCR
        response = requests.post(
            "https://api.ocr.space/parse/image", 
            data=payload, 
            files=files,
            timeout=25
        )
        
        result_json = response.json()
        plate_text = ""
        
        if result_json.get("ParsedResults"):
            parsed_text = result_json["ParsedResults"][0].get("ParsedText", "")
            plate_text = normalize_plate(parsed_text)
            
        return jsonify({
            "plate": plate_text,
            "plate_source": "opencv_contour + cloud_ocr"
        })

    except Exception as e:
        print(f"❌ [API OCR Error] Thất bại: {e}")
        return jsonify({"plate": "", "error": str(e)}), 500


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    app.run(host="0.0.0.0", port=port, debug=False)