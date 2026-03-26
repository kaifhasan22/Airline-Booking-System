# ✈️ SkyNest Airlines — Online Airline Booking System

A fully functional web-based airline booking system built with PHP, HTML, CSS, and MySQL. Users can search for flights, fill in passenger details, make bookings, and receive a digital e-ticket. The project is inspired by real-world platforms like MakeMyTrip and demonstrates full integration of front-end design with back-end database operations.

---

## 👥 Team Members

| Name | Role |
|------|------|
|Kaif Hasan Farooqui |
|Ayan Khokar |
|Deshna Jain |
|Arushi Dube |

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3 |
| Backend | PHP 7.4+ |
| Database | MySQL 8.0+ |
| Server | XAMPP (Apache + MySQL + PHP) |
| DB Manager | phpMyAdmin |

---

## ✨ Features

- 🔍 **Flight Search** — Search by departure city, arrival city, and date with dynamic filtering
- 🧍 **Passenger Booking** — Multi-step form with interactive seat map and payment method selection
- 🎫 **E-Ticket Generation** — Unique ticket number generated after every successful booking
- 📋 **My Booking** — Retrieve your booking anytime using your ticket number
- 🔐 **Admin Panel** — Password-protected dashboard to manage bookings, passengers, flights, and payments
- 💳 **Payment Tracking** — Payment records linked to each booking
- 🧳 **Baggage Info** — Baggage details stored per passenger

---

## 📁 File Structure

```
skynest/
│
├── db.php              # MySQL database connection
├── index.php           # Home page with hero section and flight search
├── search.php          # Flight results with filters and sort
├── passenger.php       # Passenger form, seat map, and payment
├── confirmation.php    # E-ticket confirmation page
├── admin.php           # Admin dashboard
├── my_booking.php      # Booking retrieval by ticket number
└── style.css           # Global stylesheet
```

---

## 🗄️ Database

**Database name:** `airline_booking_system`

Contains **10 MySQL tables:**

`airlines` · `airports` · `flights` · `flight_schedule` · `passengers` · `bookings` · `tickets` · `payments` · `baggage` · `crew`

---

## ⚙️ Installation & Setup

### Prerequisites

- [XAMPP](https://www.apachefriends.org/) v8.x or above
- PHP 7.4+
- MySQL 8.0+
- A modern web browser (Chrome / Firefox / Edge)

### Steps

1. **Clone or download** this repository into your XAMPP `htdocs` folder:
   ```
   C:\xampp\htdocs\skynest\
   ```

2. **Start XAMPP** — make sure both **Apache** and **MySQL** are running.

3. **Import the database:**
   - Open [phpMyAdmin](http://localhost/phpmyadmin)
   - Create a new database named `airline_booking_system`
   - Import the provided `.sql` file

4. **Configure the database connection** in `db.php`:
   ```php
   $conn = new mysqli("localhost", "root", "", "airline_booking_system");
   ```

5. **Open the app** in your browser:
   ```
   http://localhost/skynest/
   ```

---

## 🧪 Testing

| # | Test Case | Expected Result | Status |
|---|-----------|----------------|--------|
| 1 | Search with valid from/to cities | Flight cards displayed | ✅ Pass |
| 2 | Search with no city selected | All flights shown | ✅ Pass |
| 3 | Submit passenger form with all fields | Data saved, redirect to confirmation | ✅ Pass |
| 4 | Submit form with missing required fields | Error message shown | ✅ Pass |
| 5 | Submit with invalid email format | Validation error shown | ✅ Pass |
| 6 | View confirmation page | Full ticket details shown | ✅ Pass |
| 7 | Admin login with wrong password | Access denied | ✅ Pass |
| 8 | Admin delete booking | Records removed from 4 tables | ✅ Pass |
| 9 | Search valid ticket number | Full e-ticket displayed | ✅ Pass |
| 10 | Search invalid ticket number | Error message shown | ✅ Pass |

---

## 💡 Key Technical Concepts

- **SQL JOINs** — Combines data across multiple tables on all pages
- **Prepared Statements** — Prevents SQL injection during data insertion
- **Database Transactions** — Atomic multi-table operations ensure data integrity
- **PHP Sessions** — Maintains admin login state across pages
- **Responsive Design** — CSS media queries for mobile/tablet compatibility

---

## 📌 Notes

- This is an **academic project** developed for educational purposes (2025).
- The system runs on a **local XAMPP server** and is not deployed to production.
- Admin credentials are set within `admin.php` using PHP sessions.

---

## 📄 License

This project is for academic use only. All rights reserved © 2026 SkyNest Airlines Team.
