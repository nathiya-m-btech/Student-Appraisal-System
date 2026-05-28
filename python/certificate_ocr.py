#!/usr/bin/env python3
import sys, os, re
from PIL import Image, ImageFilter
import pytesseract
import mysql.connector

# -------------------------------
# CONFIGURE TESSERACT PATH
# -------------------------------
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract.exe"

# -------------------------------
# ARGUMENT CHECK
# -------------------------------
if len(sys.argv) < 3:
    print("USAGE: python certificate_cgpa_ocr.py <image_path> <row_id>")
    sys.exit(1)

img_path = sys.argv[1]
row_id = sys.argv[2]

if not os.path.exists(img_path):
    print("ERROR: Image file not found")
    sys.exit(1)

# -------------------------------
# OCR PROCESS
# -------------------------------
try:
    img = Image.open(img_path).convert('L')
    img = img.filter(ImageFilter.MedianFilter())

    w, h = img.size
    if w < 1200:
        img = img.resize((int(w*1.5), int(h*1.5)))

    text = pytesseract.image_to_string(img)
    print("Extracted Text:\n", text.strip())

except Exception as e:
    print("OCR_FAILED:", str(e))
    sys.exit(1)

# -------------------------------
# PARSE CGPA AND YEAR
# -------------------------------
cgpa_match = re.search(r'([0-9]\.[0-9]{2,3})', text)
cgpa = float(cgpa_match.group(1)) if cgpa_match else 0

year_match = re.search(r'(20\d{2})', text)
year = int(year_match.group(1)) if year_match else 2024

institution = "M. Kumarasamy College of Engineering"
board = "Anna University"

# -------------------------------
# CALCULATE CGPA SCORE
# -------------------------------
def score(c):
    if c >= 9: return 10
    if c >= 8: return 9
    if c >= 7: return 7
    if c >= 6: return 5
    if c >= 5: return 3
    return 1

cgpa_score = score(cgpa)

# -------------------------------
# UPDATE DATABASE
# -------------------------------
try:
    conn = mysql.connector.connect(
        host="localhost", user="root", password="", database="student_appraisal"
    )
    cur = conn.cursor()
    sql = """
    UPDATE academic_cgpa
    SET institution=%s, board=%s, year=%s, cgpa=%s, cgpa_score=%s
    WHERE id=%s
    """
    cur.execute(sql, (institution, board, year, cgpa, cgpa_score, row_id))
    conn.commit()
    cur.close()
    conn.close()
    print(f"Database updated successfully for row_id={row_id}")

except Exception as e:
    print("DB_UPDATE_FAILED:", str(e))
    sys.exit(1)
