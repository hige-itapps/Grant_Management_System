CREATE DATABASE  IF NOT EXISTS `hige` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `hige`;
-- MySQL dump 10.13  Distrib 5.7.19, for Win64 (x86_64)
--
-- Host: 141.218.158.65    Database: hige
-- ------------------------------------------------------
-- Server version	5.7.19

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
  `Name` varchar(100) NOT NULL,
  `Department` varchar(80) NOT NULL,
  `MailStop` int(4) DEFAULT NULL,
  `Email` varchar(254) NOT NULL,
  `Title` varchar(300) NOT NULL,
  `TravelStart` date NOT NULL,
  `TravelEnd` date NOT NULL,
  `EventStart` date NOT NULL,
  `EventEnd` date NOT NULL,
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
  `DepartmentChairEmail` varchar(254) NOT NULL,
  `DepartmentChairSignature` varchar(100) DEFAULT NULL,
  `Approved` tinyint(1) DEFAULT NULL,
  `AmountAwarded` decimal(10,2) DEFAULT NULL,
  `OnHold` tinyint(4) NOT NULL DEFAULT '0',
  `NextCycle` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `applicant` (`Applicant`) USING BTREE,
  CONSTRAINT `fk_applications_applicant_id` FOREIGN KEY (`Applicant`) REFERENCES `applicants` (`BroncoNetID`)
) ENGINE=InnoDB AUTO_INCREMENT=166 DEFAULT CHARSET=latin1;
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
  `Comment` varchar(100) NOT NULL,
  PRIMARY KEY (`BudgetItemID`),
  KEY `INDEX` (`ApplicationID`),
  CONSTRAINT `fk_applications_budgets_applications_id` FOREIGN KEY (`ApplicationID`) REFERENCES `applications` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=297 DEFAULT CHARSET=latin1;
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
-- Table structure for table `follow_up_approval`
--

DROP TABLE IF EXISTS `follow_up_approval`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_up_approval` (
  `BroncoNetID` varchar(20) NOT NULL,
  `Name` varchar(100) NOT NULL,
  PRIMARY KEY (`BroncoNetID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `follow_up_reports`
--

DROP TABLE IF EXISTS `follow_up_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `follow_up_reports` (
  `ApplicationID` int(11) NOT NULL,
  `TravelStart` date NOT NULL,
  `TravelEnd` date NOT NULL,
  `EventStart` date NOT NULL,
  `EventEnd` date NOT NULL,
  `ProjectSummary` varchar(3200) NOT NULL,
  `TotalAwardSpent` decimal(10,2) NOT NULL,
  `Approved` tinyint(1) DEFAULT NULL,
  `Date` date NOT NULL,
  PRIMARY KEY (`ApplicationID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-16 15:22:07
