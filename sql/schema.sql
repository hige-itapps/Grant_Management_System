CREATE DATABASE  IF NOT EXISTS `iefdf` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `iefdf`;
-- MySQL dump 10.13  Distrib 5.7.19, for Win64 (x86_64)
--
-- Host: 141.218.158.65    Database: iefdf
-- ------------------------------------------------------
-- Server version	5.5.5-10.2.8-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `administrators`
--

DROP TABLE IF EXISTS `administrators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `administrators` (
  `BroncoNetID` varchar(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicants` (
  `BroncoNetID` varchar(20) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `application_approval`
--

DROP TABLE IF EXISTS `application_approval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `application_approval` (
  `BroncoNetID` varchar(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Applicant` varchar(20) NOT NULL,
  `Date` date NOT NULL,
  `NextCycle` tinyint(4) NOT NULL DEFAULT 0,
  `Name` varchar(100) NOT NULL,
  `Email` varchar(254) NOT NULL,
  `Department` varchar(80) NOT NULL,
  `DepartmentChairEmail` varchar(254) NOT NULL,
  `TravelStart` date NOT NULL,
  `TravelEnd` date NOT NULL,
  `EventStart` date NOT NULL,
  `EventEnd` date NOT NULL,
  `Title` varchar(300) NOT NULL,
  `Destination` varchar(100) NOT NULL,
  `AmountRequested` decimal(10,2) NOT NULL,
  `IsResearch` tinyint(1) NOT NULL,
  `IsConference` tinyint(1) NOT NULL,
  `IsCreativeActivity` tinyint(1) NOT NULL,
  `IsOtherEventText` varchar(400) DEFAULT NULL,
  `OtherFunding` varchar(400) NOT NULL,
  `ProposalSummary` varchar(1400) NOT NULL,
  `FulfillsGoal1` tinyint(1) NOT NULL,
  `FulfillsGoal2` tinyint(1) NOT NULL,
  `FulfillsGoal3` tinyint(1) NOT NULL,
  `FulfillsGoal4` tinyint(1) NOT NULL,
  `DepartmentChairSignature` varchar(100) DEFAULT NULL,
  `AmountAwarded` decimal(10,2) DEFAULT NULL,
  `Status` varchar(20) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `applicant` (`Applicant`) USING BTREE,
  CONSTRAINT `fk_applications_applicant_id` FOREIGN KEY (`Applicant`) REFERENCES `applicants` (`BroncoNetID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `applications_budgets`
--

DROP TABLE IF EXISTS `applications_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applications_budgets` (
  `BudgetItemID` int(11) NOT NULL AUTO_INCREMENT,
  `ApplicationID` int(11) NOT NULL,
  `Name` varchar(25) NOT NULL,
  `Cost` decimal(10,2) NOT NULL,
  `Details` varchar(100) NOT NULL,
  PRIMARY KEY (`BudgetItemID`),
  KEY `INDEX` (`ApplicationID`),
  CONSTRAINT `fk_applications_budgets_applications_id` FOREIGN KEY (`ApplicationID`) REFERENCES `applications` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `committee`
--

DROP TABLE IF EXISTS `committee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `committee` (
  `BroncoNetID` varchar(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `emails`
--

DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ApplicationID` int(11) NOT NULL,
  `Subject` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `Time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`),
  KEY `fk_emails_applications_id_idx` (`ApplicationID`),
  CONSTRAINT `fk_emails_applications_id` FOREIGN KEY (`ApplicationID`) REFERENCES `applications` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `final_report_approval`
--

DROP TABLE IF EXISTS `final_report_approval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_report_approval` (
  `BroncoNetID` varchar(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `final_reports`
--

DROP TABLE IF EXISTS `final_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `final_reports` (
  `ApplicationID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `TravelStart` date NOT NULL,
  `TravelEnd` date NOT NULL,
  `EventStart` date NOT NULL,
  `EventEnd` date NOT NULL,
  `TotalAwardSpent` decimal(10,2) NOT NULL,
  `ProjectSummary` varchar(3200) NOT NULL,
  `Status` varchar(20) NOT NULL,
  PRIMARY KEY (`ApplicationID`),
  CONSTRAINT `fk_final_reports_applications_id` FOREIGN KEY (`ApplicationID`) REFERENCES `applications` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notes`
--

DROP TABLE IF EXISTS `notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notes` (
  `ApplicationID` int(11) NOT NULL,
  `Note` text DEFAULT NULL,
  PRIMARY KEY (`ApplicationID`),
  KEY `fk_notes_applications_id_idx` (`ApplicationID`),
  CONSTRAINT `fk_notes_applications_id` FOREIGN KEY (`ApplicationID`) REFERENCES `applications` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `variables`
--

DROP TABLE IF EXISTS `variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `variables` (
  `Name` varchar(30) NOT NULL,
  `Value` text DEFAULT NULL,
  PRIMARY KEY (`Name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-10-31 12:58:32
