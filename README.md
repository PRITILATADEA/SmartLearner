# SmartLearner

SmartLearner is a web-based classroom management system for teachers and students. It helps users create and manage classrooms, schedule sessions, organize quizzes, and track student progress. Teachers can set up classes and quizzes, while students can enroll, participate, and view their results.

---

## Features

- User registration and login
- Teacher dashboard to create and manage classrooms
- Student dashboard to enroll in and leave classrooms
- Classroom scheduling (multiple days, lab sessions)
- Quiz creation, publishing, updating, and evaluation
- Students can attempt quizzes and view marks
- Notifications for comments, quizzes, and classroom activities
- Add multiple teachers to a classroom
- Archive classrooms when completed
- User profile management

---

## Tech Stack

- PHP (8.x recommended)
- MySQL
- HTML, CSS (Tailwind CSS)
- JavaScript

---

## Installation & Setup

1. **Clone the repository**
    ```bash
    git clone https://github.com/PRITILATADEA/SmartLearner.git
    cd SmartLearner
    ```
2. **Set up the database**
    - Create a MySQL database.
    - Import the schema using:
      ```bash
      mysql -u <username> -p <database_name> < database.sql
      ```
    - Update database connection settings in `includes/db.php`.
3. **Configure your server**
    - Use Apache, Nginx, or any server supporting PHP.
    - Place the project files in your web server's root directory.
4. **Access the app**
    - Open your browser and go to `http://localhost/SmartLearner/` (or your server’s address).
5. **Register new users**
    - Use the registration page to create accounts for teachers and students.

---

## Project File Structure

- `dashboard.php` — User dashboard
- `classroom.php` — Main classroom page
- `classroom/` — Classroom-related modules, including quizzes
- `classroom/quiz.php` — Quiz module
- `profile.php` — User profile management
- `register.php`, `login.php`, `logout.php` — Authentication
- `schedule.php` — Class scheduling
- `database.sql` — Database schema
- `includes/` — Backend scripts for quizzes, notifications, authentication, and other logic
- `css/` — Stylesheets
- `images/` — Image assets

---

## Security Highlights

- User access control for classrooms and quizzes
- All database queries use prepared statements
- Passwords are securely handled
- Input validation on forms

---

## Contributors

- [@pritilatadea](https://github.com/pritilatadea)
- [@tafsiruzzaman](https://github.com/tafsiruzzaman)
- [@Nayma-Amin](https://github.com/Nayma-Amin)

---

## Usage Notes

- Register as a teacher or student to get started.
- Teachers can create classrooms, add other teachers, schedule sessions, and create quizzes.
- Students can enroll in classrooms, participate in quizzes, and view their marks.
- Notifications help users keep track of classroom activities and quiz updates.
- The archive feature allows teachers to close classrooms when courses are finished.

---

## License

MIT License. © [@pritilatadea](https://github.com/pritilatadea), [@tafsiruzzaman](https://github.com/tafsiruzzaman), [@Nayma-Amin](https://github.com/Nayma-Amin)