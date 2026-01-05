from flask import Flask, request, jsonify
import cv2
import numpy as np
import pytesseract
import re
import uuid
import os

app = Flask(__name__)

# Windows path
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

DEBUG = True
DEBUG_DIR = "debug"
os.makedirs(DEBUG_DIR, exist_ok=True)


# ========================
# Utils
# ========================

def normalize_plate(text: str) -> str:
    text = text.upper()
    text = re.sub(r"[^A-Z0-9]", "", text)
    return text


def preprocess(img):
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    blur = cv2.bilateralFilter(gray, 11, 17, 17)
    _, thresh = cv2.threshold(
        blur, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU
    )
    return thresh


def ocr_image(img):
    for psm in [7, 8, 6, 11]:
        config = (
            f"--oem 3 --psm {psm} "
            "-c tessedit_char_whitelist=ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"
        )
        text = pytesseract.image_to_string(img, config=config)
        text = normalize_plate(text)
        if len(text) >= 5:
            return text
    return ""

def detect_plate(img):
    h_img, w_img, _ = img.shape

    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    blur = cv2.GaussianBlur(gray, (5, 5), 0)
    edged = cv2.Canny(blur, 50, 200)

    contours, _ = cv2.findContours(edged, cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    contours = sorted(contours, key=cv2.contourArea, reverse=True)

    for c in contours[:5]:  # chỉ xét top 5
        x, y, w, h = cv2.boundingRect(c)
        ratio = w / float(h)

        # SIẾT CHẶT
        if (
            2.5 < ratio < 5.5 and
            w > 0.3 * w_img and
            h > 0.08 * h_img
        ):
            return img[y:y+h, x:x+w]

    return None
@app.route("/predict", methods=["POST"])
def predict():
    file = request.files["file"]
    img = cv2.imdecode(np.frombuffer(file.read(), np.uint8), cv2.IMREAD_COLOR)

    if DEBUG:
        cv2.imwrite(f"{DEBUG_DIR}/input.jpg", img)

    processed = preprocess(img)
    plate = ocr_image(processed)

    if DEBUG:
        cv2.imwrite(f"{DEBUG_DIR}/processed.jpg", processed)

    return jsonify({ "plate": plate })

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=8001, debug=True)
