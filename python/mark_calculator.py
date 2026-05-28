import sys
import mysql.connector

if len(sys.argv) < 2:
    print("0")
    sys.exit(1)

student_id = int(sys.argv[1])

cfg = {
    "host": "127.0.0.1",
    "user": "root",
    "password": "",
    "database": "student_appraisal"
}

db = mysql.connector.connect(**cfg)
cur = db.cursor()

cur.execute("""
    SELECT test_mark, attendance_mark, certificate_mark
    FROM students WHERE student_id=%s
""", (student_id,))
row = cur.fetchone()

if row:
    test, attendance, certificate = row
    total = test + attendance + certificate
    print(total)
else:
    print(0)

cur.close()
db.close()
