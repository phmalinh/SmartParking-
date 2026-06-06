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

# 🚀 API Key OCR Space của bạn (Giữ nguyên)
OCR_SPACE_API_KEY = "K85568556588957"


def normalize_plate(text: str) -> str:
    """Làm sạch chuỗi nhận diện: viết hoa và chỉ giữ lại A-Z, 0-9."""
    if not text:
        return ""
    text = text.upper()
    text = re.sub(r"[^A-Z0-9]", "", text)
    return text


def detect_and_crop_plate(img):
    """
    TỐI ƯU: Sử dụng OpenCV để tìm chính xác vùng biển số xe và cắt bỏ nhiễu.
    """
    try:
        h_img, w_img, _ = img.shape

        # 1. Chuyển đổi màu và làm mịn ảnh để giảm nhiễu tốt hơn
        gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
        blur = cv2.bilateralFilter(gray, 11, 17, 17) # Giữ cạnh tốt hơn Gaussian
        
        # 2. Phát hiện các cạnh biên chuẩn cho vật thể hình học
        edged = cv2.Canny(blur, 30, 200)

        # 3. Tìm và phân tích các đường viền
        contours, _ = cv2.findContours(edged.copy(), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
        contours = sorted(contours, key=cv2.contourArea, reverse=True)

        print(f"📊 [OpenCV] Đã tìm thấy {len(contours)} đường viền.")

        # 4. Tìm đường viền có hình dạng chữ nhật giống biển số nhất
        best_plate_contour = None
        for c in contours[:15]: # Xét top 15 đường viền lớn nhất
            # Phân tích độ thẳng của cạnh
            peri = cv2.arcLength(c, True)
            approx = cv2.approxPolyDP(c, 0.02 * peri, True)
            
            # Nếu đường viền có 4 cạnh (hoặc 5-6 cạnh cho biển số mờ)
            if 4 <= len(approx) <= 6:
                x, y, w, h = cv2.boundingRect(approx)
                ratio = w / float(h)
                area = w * h
                
                # SIẾT CHẶT TỶ LỆ HÌNH HỌC CỦA BIỂN SỐ XE
                # Tỷ lệ rộng/cao điển hình: 2.0 - 5.5
                if 2.2 < ratio < 5.0:
                    # Kích thước phải đủ lớn nhưng không được chiếm quá 80% ảnh
                    if area > 0.03 * (w_img * h_img) and w < 0.8 * w_img:
                        best_plate_contour = approx
                        break # Đã tìm thấy, dừng quét

        # 5. Nếu tìm thấy, thực hiện cắt và trả về
        if best_plate_contour is not None:
            x, y, w, h = cv2.boundingRect(best_plate_contour)
            
            # Thêm một chút lề để OCR đọc tốt hơn (10 pixels)
            pad = 10
            x1 = max(0, x - pad)
            y1 = max(0, y - pad)
            x2 = min(w_img, x + w + pad)
            y2 = min(h_img, y + h + pad)
            
            cropped_plate = img[y1:y2, x1:x2]
            print("🎯 [OpenCV] Đã tìm thấy và cắt chính xác biển số xe.")
            return cropped_plate
            
        print("⚠️ [OpenCV] Không tìm thấy biển số phù hợp (Dùng ảnh gốc).")

    except Exception as e:
        print(f"❌ [OpenCV Error] Không cắt được ảnh: {e}")
        
    # Nếu không tìm thấy hoặc lỗi, gửi ảnh gốc đi làm fallback
    return img


@app.route("/predict", methods=["POST"])
def predict():
    """
    API Nhận diện biển số xe: Cắt ảnh nội bộ -> Gọi Cloud OCR -> Trả về text chuẩn.
    """
    file = request.files.get("file")
    if file is None:
        return jsonify({"plate": "", "error": "Không có file"}), 400

    try:
        # Đọc dữ liệu ảnh từ request
        img_bytes = file.read()
        img = cv2.imdecode(np.frombuffer(img_bytes, np.uint8), cv2.IMREAD_COLOR)
        if img is None:
            return jsonify({"plate": "", "error": "Ảnh không hợp lệ"}), 400

        # 🚀 BƯỚC CẮT ẢNH: Tự động tìm và cắt vùng chứa biển số
        # Đây là bước quan trọng nhất để sửa lỗi đọc nhầm chữ rác
        processed_img = detect_and_crop_plate(img)

        # Chuyển đổi ảnh đã cắt về dạng byte để gửi HTTP
        _, buffer = cv2.imencode('.jpg', processed_img)
        cropped_bytes = buffer.tobytes()
        
        # Cấu hình tham số gửi tới API OCR Space
        payload = {
            "apikey": OCR_SPACE_API_KEY,
            "language": "eng", # Sử dụng tiếng Anh
            "isOverlayRequired": False,
            "OCREngine": "2", # Engine 2 tối ưu cho văn bản ngắn, biển số
            "scale": True,    # Tự động phóng to ảnh nhỏ để đọc tốt hơn
        }
        
        files = {
            "file": ("cropped_plate.jpg", cropped_bytes, "image/jpeg")
        }
        
        # Gửi request lên đám mây (Cloud)
        response = requests.post(
            "https://api.ocr.space/parse/image", 
            data=payload, 
            files=files,
            timeout=25 # Đặt timeout phòng trường hợp mạng chậm
        )
        
        result_json = response.json()
        plate_text = ""
        
        if result_json.get("ParsedResults"):
            # Lấy chuỗi văn bản đã nhận diện
            parsed_text = result_json["ParsedResults"][0].get("ParsedText", "")
            # Làm sạch chuỗi
            plate_text = normalize_plate(parsed_text)
            
        return jsonify({
            "plate": plate_text,
            "plate_source": "opencv_contours + cloud_ocr_api"
        })

    except requests.exceptions.Timeout:
        return jsonify({"plate": "", "error": "Lỗi kết nối API OCR"}), 504
    except Exception as e:
        print(f"❌ [API OCR Error] Hệ thống gặp lỗi: {e}")
        return jsonify({"plate": "", "error": str(e)}), 500


# Render yêu cầu lắng nghe cổng do hệ thống cấp
if __name__ == "__main__":
    port = int(os.environ.get("PORT", 8001))
    # Đặt debug=False trên môi trường Render
    app.run(host="0.0.0.0", port=port, debug=False)