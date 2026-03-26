-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2026 at 02:23 PM
-- Server version: 8.0.44
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `airline_booking_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `airlines`
--

CREATE TABLE `airlines` (
  `airline_id` int NOT NULL,
  `airline_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `headquarter` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `airlines`
--

INSERT INTO `airlines` (`airline_id`, `airline_name`, `email`, `headquarter`) VALUES
(1, 'IndiGo', 'contact@indigo.com', 'Gurgaon'),
(2, 'Air India', 'support@airindia.com', 'New Delhi'),
(3, 'SpiceJet', 'info@spicejet.com', 'Gurgaon'),
(4, 'Vistara', 'contact@vistara.com', 'Delhi'),
(5, 'Akasa Air', 'support@akasaair.com', 'Mumbai');

-- --------------------------------------------------------

--
-- Table structure for table `airports`
--

CREATE TABLE `airports` (
  `airport_id` int NOT NULL,
  `airport_name` varchar(100) DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `airports`
--

INSERT INTO `airports` (`airport_id`, `airport_name`, `city`, `state`, `country`) VALUES
(1, 'Indira Gandhi International Airport', 'Delhi', 'Delhi', 'India'),
(2, 'Chhatrapati Shivaji Airport', 'Mumbai', 'Maharashtra', 'India'),
(3, 'Kempegowda International Airport', 'Bangalore', 'Karnataka', 'India'),
(4, 'Netaji Subhash Chandra Bose Airport', 'Kolkata', 'West Bengal', 'India'),
(5, 'Rajiv Gandhi International Airport', 'Hyderabad', 'Telangana', 'India');

-- --------------------------------------------------------

--
-- Table structure for table `baggage`
--

CREATE TABLE `baggage` (
  `baggage_id` int NOT NULL,
  `passenger_id` int DEFAULT NULL,
  `weight` int DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `baggage`
--

INSERT INTO `baggage` (`baggage_id`, `passenger_id`, `weight`, `type`) VALUES
(1, 1, 15, 'Check-in'),
(2, 2, 10, 'Cabin'),
(3, 3, 20, 'Check-in'),
(4, 4, 12, 'Cabin'),
(5, 5, 18, 'Check-in'),
(6, 1, 15, 'Check-in'),
(7, 2, 10, 'Cabin'),
(8, 3, 20, 'Check-in'),
(9, 4, 12, 'Cabin'),
(10, 5, 18, 'Check-in');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` int NOT NULL,
  `passenger_id` int DEFAULT NULL,
  `flight_id` int DEFAULT NULL,
  `booking_date` date DEFAULT NULL,
  `seat_number` varchar(10) DEFAULT NULL,
  `booking_status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `passenger_id`, `flight_id`, `booking_date`, `seat_number`, `booking_status`) VALUES
(1, 1, 1, '2026-04-01', '12A', 'Confirmed'),
(2, 2, 2, '2026-04-02', '14B', 'Confirmed'),
(3, 3, 3, '2026-04-03', '15C', 'Pending'),
(4, 4, 4, '2026-04-04', '10D', 'Confirmed'),
(5, 5, 5, '2026-04-05', '9F', 'Cancelled');

-- --------------------------------------------------------

--
-- Table structure for table `crew`
--

CREATE TABLE `crew` (
  `crew_id` int NOT NULL,
  `crew_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `airline_id` int DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `crew`
--

INSERT INTO `crew` (`crew_id`, `crew_name`, `role`, `airline_id`, `phone`) VALUES
(1, 'Rajesh Kumar', 'Pilot', 1, '9812345678'),
(2, 'Amit Verma', 'Co-Pilot', 2, '9823456789'),
(3, 'Neha Sharma', 'Cabin Crew', 3, '9834567890'),
(4, 'Pooja Singh', 'Cabin Crew', 4, '9845678901'),
(5, 'Karan Patel', 'Pilot', 5, '9856789012');

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `flight_id` int NOT NULL,
  `airline_id` int DEFAULT NULL,
  `departure_airport` int DEFAULT NULL,
  `arrival_airport` int DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `price` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`flight_id`, `airline_id`, `departure_airport`, `arrival_airport`, `departure_time`, `arrival_time`, `price`) VALUES
(1, 1, 1, 2, '2026-04-10 08:00:00', '2026-04-10 10:00:00', 5000),
(2, 2, 2, 3, '2026-04-11 09:30:00', '2026-04-11 11:30:00', 6500),
(3, 3, 3, 4, '2026-04-12 07:00:00', '2026-04-12 09:15:00', 5500),
(4, 4, 4, 5, '2026-04-13 06:45:00', '2026-04-13 08:45:00', 6000),
(5, 5, 5, 1, '2026-04-14 10:15:00', '2026-04-14 12:30:00', 7000);

-- --------------------------------------------------------

--
-- Table structure for table `flight_schedule`
--

CREATE TABLE `flight_schedule` (
  `schedule_id` int NOT NULL,
  `flight_id` int DEFAULT NULL,
  `departure_date` date DEFAULT NULL,
  `gate_number` varchar(10) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `passengers`
--

CREATE TABLE `passengers` (
  `passenger_id` int NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `passport_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `passengers`
--

INSERT INTO `passengers` (`passenger_id`, `first_name`, `last_name`, `email`, `phone`, `passport_number`) VALUES
(1, 'Student1', 'Sharma', 'student1@gmail.com', '9876543210', 'P123456'),
(2, 'Student2', 'Verma', 'student2@gmail.com', '9123456780', 'P234567'),
(3, 'Rohit', 'Singh', 'rohit@gmail.com', '9871112233', 'P345678'),
(4, 'Priya', 'Mehta', 'priya@gmail.com', '9872223344', 'P456789'),
(5, 'Ankit', 'Jain', 'ankit@gmail.com', '9873334455', 'P567890');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` int DEFAULT NULL,
  `payment_method` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_date`, `amount`, `payment_method`) VALUES
(1, 1, '2026-04-01', 5000, 'Credit Card'),
(2, 2, '2026-04-02', 6500, 'UPI'),
(3, 3, '2026-04-03', 5500, 'Debit Card'),
(4, 4, '2026-04-04', 6000, 'Net Banking'),
(5, 5, '2026-04-05', 7000, 'UPI');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `ticket_id` int NOT NULL,
  `booking_id` int DEFAULT NULL,
  `ticket_number` varchar(20) DEFAULT NULL,
  `seat_class` varchar(20) DEFAULT NULL,
  `price` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`ticket_id`, `booking_id`, `ticket_number`, `seat_class`, `price`) VALUES
(1, 1, 'TKT1001', 'Economy', 5000),
(2, 2, 'TKT1002', 'Business', 6500),
(3, 3, 'TKT1003', 'Economy', 5500),
(4, 4, 'TKT1004', 'Economy', 6000),
(5, 5, 'TKT1005', 'Business', 7000);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `airlines`
--
ALTER TABLE `airlines`
  ADD PRIMARY KEY (`airline_id`);

--
-- Indexes for table `airports`
--
ALTER TABLE `airports`
  ADD PRIMARY KEY (`airport_id`);

--
-- Indexes for table `baggage`
--
ALTER TABLE `baggage`
  ADD PRIMARY KEY (`baggage_id`),
  ADD KEY `passenger_id` (`passenger_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `passenger_id` (`passenger_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `crew`
--
ALTER TABLE `crew`
  ADD PRIMARY KEY (`crew_id`),
  ADD KEY `airline_id` (`airline_id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`flight_id`),
  ADD KEY `airline_id` (`airline_id`);

--
-- Indexes for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `flight_id` (`flight_id`);

--
-- Indexes for table `passengers`
--
ALTER TABLE `passengers`
  ADD PRIMARY KEY (`passenger_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`ticket_id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `airlines`
--
ALTER TABLE `airlines`
  MODIFY `airline_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `airports`
--
ALTER TABLE `airports`
  MODIFY `airport_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `baggage`
--
ALTER TABLE `baggage`
  MODIFY `baggage_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `crew`
--
ALTER TABLE `crew`
  MODIFY `crew_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `flight_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  MODIFY `schedule_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passengers`
--
ALTER TABLE `passengers`
  MODIFY `passenger_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `ticket_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `baggage`
--
ALTER TABLE `baggage`
  ADD CONSTRAINT `baggage_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`passenger_id`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`passenger_id`) REFERENCES `passengers` (`passenger_id`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`);

--
-- Constraints for table `crew`
--
ALTER TABLE `crew`
  ADD CONSTRAINT `crew_ibfk_1` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`airline_id`);

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`airline_id`);

--
-- Constraints for table `flight_schedule`
--
ALTER TABLE `flight_schedule`
  ADD CONSTRAINT `flight_schedule_ibfk_1` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`flight_id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
