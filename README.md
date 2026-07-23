# Student Appraisal System 

Overview:
A web-based Student Appraisal System that scores students automatically from uploaded certificates and profile data using SOP rules, AHP, TOPSIS and Random Forest.

Quick Setup:
1. Copy `public/` to your webroot (XAMPP htdocs/student_appraisal/public).
2. Place `php/` in a secure folder (outside webroot recommended) or alongside public for testing.
3. Create DB: run `sql/student_appraisal.sql` in phpMyAdmin.
4. Configure `php/db_connect.php` DB credentials.
5. Install Python 3.10+ and create venv in `python/`.
   ```
   cd python
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```
6. Install Tesseract OCR on your machine and add to PATH.
7. Configure `python/*.py` DB credentials if necessary.
8. Create initial admin user in `users` table.

Run:
- Start Apache & MySQL (XAMPP)
- Visit `http://localhost/student_appraisal/public/enhanced_login.html`
- Login and test upload & scoring.

Notes:
- This is a starter implementation — refine parsing, secure uploads, and productionize.
