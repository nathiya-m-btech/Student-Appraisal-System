#!/usr/bin/env python3
import mysql.connector, pandas as pd, numpy as np, joblib
from scoring.ahp_topsis import topsis, build_criteria_vector

# DB connection
cfg = {'host':'127.0.0.1','user':'root','password':'','database':'student_appraisal'}
cnx = mysql.connector.connect(**cfg)
cur = cnx.cursor(dictionary=True)

# Fetch all students
cur.execute("SELECT student_id, name, cgpa, coding_score FROM students")
students = cur.fetchall()
if not students:
    print("No students found")
    exit(0)

rows = []
ids = []

for s in students:
    ids.append(s['student_id'])
    # Ensure numeric values, default to 0 if None
    cgpa = float(s['cgpa']) if s['cgpa'] else 0.0
    coding = int(s['coding_score']) if s['coding_score'] else 0
    row = {'cgpa': cgpa, 'coding_score': coding, 'projects':0, 'hackathon':0, 'internship':0, 'sports':0}
    rows.append(build_criteria_vector(row))

cols = ['cgpa','coding','projects','hackathon','internship','sports']
df = pd.DataFrame(rows, columns=cols)

# Weights for TOPSIS
weights = np.array([0.25,0.2,0.15,0.15,0.1,0.15])
weights = weights[:df.shape[1]]
scores = topsis(df, weights)

for idx, sid in enumerate(ids):
    topsis_score = float(scores[idx])
    final_score = topsis_score  # default final score
    rf_label = 'no_model'

    # Try RF model prediction
    try:
        clf = joblib.load('python/scoring/model_rf.pkl')
        rf_label = str(clf.predict(df.iloc[[idx]].values)[0])
        final_score = topsis_score + int(rf_label)  # Or any formula you use
    except Exception as e:
        rf_label = 'no_model'

    # Insert/update rankings
    cur2 = cnx.cursor()
    cur2.execute("""
        REPLACE INTO rankings 
        (student_id, topsis_score, rf_predicted_label, final_score) 
        VALUES (%s,%s,%s,%s)
    """, (sid, topsis_score, rf_label, final_score))
    cnx.commit()
    cur2.close()

cur.close()
cnx.close()
print("All students scoring done")
