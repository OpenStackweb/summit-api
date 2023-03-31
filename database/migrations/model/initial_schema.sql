-- MySQL dump 10.13  Distrib 8.0.32, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: clean_model
-- ------------------------------------------------------
-- Server version	8.0.32-0ubuntu0.20.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*!40111 SET @OLD_SQL_REQUIRE_PRIMARY_KEY = @@SQL_REQUIRE_PRIMARY_KEY, SQL_REQUIRE_PRIMARY_KEY = 0 */;

--
-- Table structure for table `ATCMember`
--

DROP TABLE IF EXISTS `ATCMember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ATCMember` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ATCMember') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ATCMember',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Username` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AltEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ATCMember`
--

LOCK TABLES `ATCMember` WRITE;
/*!40000 ALTER TABLE `ATCMember` DISABLE KEYS */;
/*!40000 ALTER TABLE `ATCMember` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AUCMetric`
--

DROP TABLE IF EXISTS `AUCMetric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AUCMetric` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AUCMetric') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AUCMetric',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Identifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ValueDescription` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Expires` datetime DEFAULT NULL,
  `FoundationMemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `FoundationMemberID` (`FoundationMemberID`),
  KEY `Identifier` (`Identifier`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AUCMetric`
--

LOCK TABLES `AUCMetric` WRITE;
/*!40000 ALTER TABLE `AUCMetric` DISABLE KEYS */;
/*!40000 ALTER TABLE `AUCMetric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AUCMetricMissMatchError`
--

DROP TABLE IF EXISTS `AUCMetricMissMatchError`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AUCMetricMissMatchError` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AUCMetricMissMatchError') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AUCMetricMissMatchError',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ServiceIdentifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `UserIdentifier` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Solved` tinyint unsigned NOT NULL DEFAULT '0',
  `SolvedDate` datetime DEFAULT NULL,
  `SolvedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SolvedByID` (`SolvedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AUCMetricMissMatchError`
--

LOCK TABLES `AUCMetricMissMatchError` WRITE;
/*!40000 ALTER TABLE `AUCMetricMissMatchError` DISABLE KEYS */;
/*!40000 ALTER TABLE `AUCMetricMissMatchError` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AUCMetricTranslation`
--

DROP TABLE IF EXISTS `AUCMetricTranslation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AUCMetricTranslation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AUCMetricTranslation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AUCMetricTranslation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `UserIdentifier` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MappedFoundationMemberID` int DEFAULT NULL,
  `CreatorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MappedFoundationMemberID` (`MappedFoundationMemberID`),
  KEY `CreatorID` (`CreatorID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AUCMetricTranslation`
--

LOCK TABLES `AUCMetricTranslation` WRITE;
/*!40000 ALTER TABLE `AUCMetricTranslation` DISABLE KEYS */;
/*!40000 ALTER TABLE `AUCMetricTranslation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AbstractCalendarSyncWorkRequest`
--

DROP TABLE IF EXISTS `AbstractCalendarSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AbstractCalendarSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AbstractCalendarSyncWorkRequest','AdminScheduleSummitActionSyncWorkRequest','AdminSummitEventActionSyncWorkRequest','AdminSummitLocationActionSyncWorkRequest','MemberScheduleSummitActionSyncWorkRequest','MemberCalendarScheduleSummitActionSyncWorkRequest','MemberEventScheduleSummitActionSyncWorkRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AbstractCalendarSyncWorkRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` enum('ADD','REMOVE','UPDATE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ADD',
  `IsProcessed` tinyint unsigned NOT NULL DEFAULT '0',
  `ProcessedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=1531376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AbstractCalendarSyncWorkRequest`
--

LOCK TABLES `AbstractCalendarSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `AbstractCalendarSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `AbstractCalendarSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AbstractSurveyMigrationMapping`
--

DROP TABLE IF EXISTS `AbstractSurveyMigrationMapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AbstractSurveyMigrationMapping` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AbstractSurveyMigrationMapping','NewDataModelSurveyMigrationMapping','OldDataModelSurveyMigrationMapping') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AbstractSurveyMigrationMapping',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `TargetFieldID` int DEFAULT NULL,
  `TargetSurveyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TargetFieldID` (`TargetFieldID`),
  KEY `TargetSurveyID` (`TargetSurveyID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AbstractSurveyMigrationMapping`
--

LOCK TABLES `AbstractSurveyMigrationMapping` WRITE;
/*!40000 ALTER TABLE `AbstractSurveyMigrationMapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `AbstractSurveyMigrationMapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AdminScheduleSummitActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `AdminScheduleSummitActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AdminScheduleSummitActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedByID` (`CreatedByID`)
) ENGINE=InnoDB AUTO_INCREMENT=1531376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AdminScheduleSummitActionSyncWorkRequest`
--

LOCK TABLES `AdminScheduleSummitActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `AdminScheduleSummitActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `AdminScheduleSummitActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AdminSummitEventActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `AdminSummitEventActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AdminSummitEventActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitEventID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`)
) ENGINE=InnoDB AUTO_INCREMENT=1531376 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AdminSummitEventActionSyncWorkRequest`
--

LOCK TABLES `AdminSummitEventActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `AdminSummitEventActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `AdminSummitEventActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AdminSummitLocationActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `AdminSummitLocationActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AdminSummitLocationActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LocationID` (`LocationID`)
) ENGINE=InnoDB AUTO_INCREMENT=1529322 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AdminSummitLocationActionSyncWorkRequest`
--

LOCK TABLES `AdminSummitLocationActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `AdminSummitLocationActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `AdminSummitLocationActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Affiliation`
--

DROP TABLE IF EXISTS `Affiliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Affiliation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Affiliation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Affiliation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `JobTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Role` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Current` tinyint unsigned NOT NULL DEFAULT '0',
  `MemberID` int DEFAULT NULL,
  `OrganizationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `OrganizationID` (`OrganizationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=52671 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Affiliation`
--

LOCK TABLES `Affiliation` WRITE;
/*!40000 ALTER TABLE `Affiliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `Affiliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AffiliationUpdate`
--

DROP TABLE IF EXISTS `AffiliationUpdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AffiliationUpdate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AffiliationUpdate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AffiliationUpdate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `NewAffiliation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OldAffiliation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AffiliationUpdate`
--

LOCK TABLES `AffiliationUpdate` WRITE;
/*!40000 ALTER TABLE `AffiliationUpdate` DISABLE KEYS */;
/*!40000 ALTER TABLE `AffiliationUpdate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AppDevSurvey`
--

DROP TABLE IF EXISTS `AppDevSurvey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AppDevSurvey` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AppDevSurvey') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AppDevSurvey',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Toolkits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherToolkits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProgrammingLanguages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherProgrammingLanguages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `APIFormats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DevelopmentEnvironments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherDevelopmentEnvironments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherOperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ConfigTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherConfigTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StateOfOpenStack` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DocsPriority` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InteractionWithOtherClouds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherAPIFormats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GuestOperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherGuestOperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StruggleDevelopmentDeploying` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherDocsPriority` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DeploymentSurveyID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DeploymentSurveyID` (`DeploymentSurveyID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AppDevSurvey`
--

LOCK TABLES `AppDevSurvey` WRITE;
/*!40000 ALTER TABLE `AppDevSurvey` DISABLE KEYS */;
/*!40000 ALTER TABLE `AppDevSurvey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Appliance`
--

DROP TABLE IF EXISTS `Appliance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Appliance` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Appliance`
--

LOCK TABLES `Appliance` WRITE;
/*!40000 ALTER TABLE `Appliance` DISABLE KEYS */;
/*!40000 ALTER TABLE `Appliance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ArticlePage`
--

DROP TABLE IF EXISTS `ArticlePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ArticlePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Date` date DEFAULT NULL,
  `Author` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ArticlePage`
--

LOCK TABLES `ArticlePage` WRITE;
/*!40000 ALTER TABLE `ArticlePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ArticlePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ArticlePage_Live`
--

DROP TABLE IF EXISTS `ArticlePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ArticlePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Date` date DEFAULT NULL,
  `Author` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ArticlePage_Live`
--

LOCK TABLES `ArticlePage_Live` WRITE;
/*!40000 ALTER TABLE `ArticlePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ArticlePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ArticlePage_versions`
--

DROP TABLE IF EXISTS `ArticlePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ArticlePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `Date` date DEFAULT NULL,
  `Author` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ArticlePage_versions`
--

LOCK TABLES `ArticlePage_versions` WRITE;
/*!40000 ALTER TABLE `ArticlePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ArticlePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AttachmentFile`
--

DROP TABLE IF EXISTS `AttachmentFile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AttachmentFile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PageID` (`PageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AttachmentFile`
--

LOCK TABLES `AttachmentFile` WRITE;
/*!40000 ALTER TABLE `AttachmentFile` DISABLE KEYS */;
/*!40000 ALTER TABLE `AttachmentFile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AttachmentImage`
--

DROP TABLE IF EXISTS `AttachmentImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AttachmentImage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PageID` (`PageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AttachmentImage`
--

LOCK TABLES `AttachmentImage` WRITE;
/*!40000 ALTER TABLE `AttachmentImage` DISABLE KEYS */;
/*!40000 ALTER TABLE `AttachmentImage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AuditLog`
--

DROP TABLE IF EXISTS `AuditLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AuditLog` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'AuditLog',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Action` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `UserID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `UserID` (`UserID`),
  CONSTRAINT `FK_AuditLog_Member` FOREIGN KEY (`UserID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2417 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AuditLog`
--

LOCK TABLES `AuditLog` WRITE;
/*!40000 ALTER TABLE `AuditLog` DISABLE KEYS */;
/*!40000 ALTER TABLE `AuditLog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AvailabilityZone`
--

DROP TABLE IF EXISTS `AvailabilityZone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AvailabilityZone` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AvailabilityZone') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AvailabilityZone',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Location_Name` (`LocationID`,`Name`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AvailabilityZone`
--

LOCK TABLES `AvailabilityZone` WRITE;
/*!40000 ALTER TABLE `AvailabilityZone` DISABLE KEYS */;
/*!40000 ALTER TABLE `AvailabilityZone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `AvailabilityZoneDraft`
--

DROP TABLE IF EXISTS `AvailabilityZoneDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `AvailabilityZoneDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('AvailabilityZoneDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AvailabilityZoneDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Location_Name` (`LocationID`,`Name`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `AvailabilityZoneDraft`
--

LOCK TABLES `AvailabilityZoneDraft` WRITE;
/*!40000 ALTER TABLE `AvailabilityZoneDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `AvailabilityZoneDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `BatchTask`
--

DROP TABLE IF EXISTS `BatchTask`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `BatchTask` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('BatchTask') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'BatchTask',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastResponse` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastRecordIndex` int NOT NULL DEFAULT '0',
  `LastResponseDate` datetime DEFAULT NULL,
  `TotalRecords` int NOT NULL DEFAULT '0',
  `CurrentPage` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BatchTask`
--

LOCK TABLES `BatchTask` WRITE;
/*!40000 ALTER TABLE `BatchTask` DISABLE KEYS */;
/*!40000 ALTER TABLE `BatchTask` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Bio`
--

DROP TABLE IF EXISTS `Bio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Bio` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Bio') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Bio',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `JobTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Company` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Role` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoID` int DEFAULT NULL,
  `BioPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PhotoID` (`PhotoID`),
  KEY `BioPageID` (`BioPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Bio`
--

LOCK TABLES `Bio` WRITE;
/*!40000 ALTER TABLE `Bio` DISABLE KEYS */;
/*!40000 ALTER TABLE `Bio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Book`
--

DROP TABLE IF EXISTS `Book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Book` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Book') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Book',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Book`
--

LOCK TABLES `Book` WRITE;
/*!40000 ALTER TABLE `Book` DISABLE KEYS */;
/*!40000 ALTER TABLE `Book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `BookAuthor`
--

DROP TABLE IF EXISTS `BookAuthor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `BookAuthor` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('BookAuthor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'BookAuthor',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `BookAuthor`
--

LOCK TABLES `BookAuthor` WRITE;
/*!40000 ALTER TABLE `BookAuthor` DISABLE KEYS */;
/*!40000 ALTER TABLE `BookAuthor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Book_Authors`
--

DROP TABLE IF EXISTS `Book_Authors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Book_Authors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BookID` int NOT NULL DEFAULT '0',
  `BookAuthorID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `BookID` (`BookID`),
  KEY `BookAuthorID` (`BookAuthorID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Book_Authors`
--

LOCK TABLES `Book_Authors` WRITE;
/*!40000 ALTER TABLE `Book_Authors` DISABLE KEYS */;
/*!40000 ALTER TABLE `Book_Authors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COALandingPage`
--

DROP TABLE IF EXISTS `COALandingPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COALandingPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BannerTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BannerText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HandBookLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HideFee` tinyint unsigned NOT NULL DEFAULT '0',
  `AlreadyRegisteredURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSpecialCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCostSpecialOffer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamIDRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCertificationPeriod` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamRetake` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSystemRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamScoring` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamHowLongSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HidePurchaseExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideVirtualExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideHowGetStarted` tinyint unsigned NOT NULL DEFAULT '0',
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COALandingPage`
--

LOCK TABLES `COALandingPage` WRITE;
/*!40000 ALTER TABLE `COALandingPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `COALandingPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COALandingPage_Live`
--

DROP TABLE IF EXISTS `COALandingPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COALandingPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BannerTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BannerText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HandBookLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HideFee` tinyint unsigned NOT NULL DEFAULT '0',
  `AlreadyRegisteredURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSpecialCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCostSpecialOffer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamIDRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCertificationPeriod` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamRetake` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSystemRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamScoring` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamHowLongSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HidePurchaseExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideVirtualExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideHowGetStarted` tinyint unsigned NOT NULL DEFAULT '0',
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COALandingPage_Live`
--

LOCK TABLES `COALandingPage_Live` WRITE;
/*!40000 ALTER TABLE `COALandingPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `COALandingPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COALandingPage_TrainingPartners`
--

DROP TABLE IF EXISTS `COALandingPage_TrainingPartners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COALandingPage_TrainingPartners` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `COALandingPageID` int NOT NULL DEFAULT '0',
  `CompanyID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `COALandingPageID` (`COALandingPageID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COALandingPage_TrainingPartners`
--

LOCK TABLES `COALandingPage_TrainingPartners` WRITE;
/*!40000 ALTER TABLE `COALandingPage_TrainingPartners` DISABLE KEYS */;
/*!40000 ALTER TABLE `COALandingPage_TrainingPartners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COALandingPage_versions`
--

DROP TABLE IF EXISTS `COALandingPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COALandingPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `BannerTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BannerText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HandBookLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedURL3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedLabel3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HideFee` tinyint unsigned NOT NULL DEFAULT '0',
  `AlreadyRegisteredURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSpecialCost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCostSpecialOffer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamIDRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamCertificationPeriod` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamRetake` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamDuration` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamSystemRequirements` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamScoring` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExamHowLongSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GetStartedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HidePurchaseExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideVirtualExam` tinyint unsigned NOT NULL DEFAULT '0',
  `HideHowGetStarted` tinyint unsigned NOT NULL DEFAULT '0',
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COALandingPage_versions`
--

LOCK TABLES `COALandingPage_versions` WRITE;
/*!40000 ALTER TABLE `COALandingPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `COALandingPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COAProcessedFile`
--

DROP TABLE IF EXISTS `COAProcessedFile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COAProcessedFile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('COAProcessedFile') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'COAProcessedFile',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TimeStamp` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COAProcessedFile`
--

LOCK TABLES `COAProcessedFile` WRITE;
/*!40000 ALTER TABLE `COAProcessedFile` DISABLE KEYS */;
/*!40000 ALTER TABLE `COAProcessedFile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COAVerifyPage`
--

DROP TABLE IF EXISTS `COAVerifyPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COAVerifyPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TosText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COAVerifyPage`
--

LOCK TABLES `COAVerifyPage` WRITE;
/*!40000 ALTER TABLE `COAVerifyPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `COAVerifyPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COAVerifyPage_Live`
--

DROP TABLE IF EXISTS `COAVerifyPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COAVerifyPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TosText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COAVerifyPage_Live`
--

LOCK TABLES `COAVerifyPage_Live` WRITE;
/*!40000 ALTER TABLE `COAVerifyPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `COAVerifyPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `COAVerifyPage_versions`
--

DROP TABLE IF EXISTS `COAVerifyPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `COAVerifyPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `TosText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `COAVerifyPage_versions`
--

LOCK TABLES `COAVerifyPage_versions` WRITE;
/*!40000 ALTER TABLE `COAVerifyPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `COAVerifyPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CalendarSyncErrorEmailRequest`
--

DROP TABLE IF EXISTS `CalendarSyncErrorEmailRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CalendarSyncErrorEmailRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CalendarSyncInfoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CalendarSyncInfoID` (`CalendarSyncInfoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CalendarSyncErrorEmailRequest`
--

LOCK TABLES `CalendarSyncErrorEmailRequest` WRITE;
/*!40000 ALTER TABLE `CalendarSyncErrorEmailRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `CalendarSyncErrorEmailRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CalendarSyncInfo`
--

DROP TABLE IF EXISTS `CalendarSyncInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CalendarSyncInfo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CalendarSyncInfo','CalendarSyncInfoCalDav','CalendarSyncInfoOAuth2') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CalendarSyncInfo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Provider` enum('Google','Outlook','iCloud') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Google',
  `CalendarExternalId` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ETag` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Revoked` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CalendarSyncInfo`
--

LOCK TABLES `CalendarSyncInfo` WRITE;
/*!40000 ALTER TABLE `CalendarSyncInfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `CalendarSyncInfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CalendarSyncInfoCalDav`
--

DROP TABLE IF EXISTS `CalendarSyncInfoCalDav`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CalendarSyncInfoCalDav` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserName` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `UserPassword` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `UserPrincipalURL` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarDisplayName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarSyncToken` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CalendarSyncInfoCalDav`
--

LOCK TABLES `CalendarSyncInfoCalDav` WRITE;
/*!40000 ALTER TABLE `CalendarSyncInfoCalDav` DISABLE KEYS */;
/*!40000 ALTER TABLE `CalendarSyncInfoCalDav` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CalendarSyncInfoOAuth2`
--

DROP TABLE IF EXISTS `CalendarSyncInfoOAuth2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CalendarSyncInfoOAuth2` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AccessToken` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RefreshToken` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CalendarSyncInfoOAuth2`
--

LOCK TABLES `CalendarSyncInfoOAuth2` WRITE;
/*!40000 ALTER TABLE `CalendarSyncInfoOAuth2` DISABLE KEYS */;
/*!40000 ALTER TABLE `CalendarSyncInfoOAuth2` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Candidate`
--

DROP TABLE IF EXISTS `Candidate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Candidate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Candidate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Candidate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `HasAcceptedNomination` tinyint unsigned NOT NULL DEFAULT '0',
  `IsGoldMemberCandidate` tinyint unsigned NOT NULL DEFAULT '0',
  `RelationshipToOpenStack` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Experience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BoardsRole` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TopPriority` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ElectionID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `Bio` longtext COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_Candidate_Election` FOREIGN KEY (`ElectionID`) REFERENCES `Election` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_Candidate_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Candidate`
--

LOCK TABLES `Candidate` WRITE;
/*!40000 ALTER TABLE `Candidate` DISABLE KEYS */;
/*!40000 ALTER TABLE `Candidate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CandidateNomination`
--

DROP TABLE IF EXISTS `CandidateNomination`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CandidateNomination` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CandidateNomination') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CandidateNomination',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `CandidateID` int DEFAULT NULL,
  `ElectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `CandidateID` (`CandidateID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CandidateNomination`
--

LOCK TABLES `CandidateNomination` WRITE;
/*!40000 ALTER TABLE `CandidateNomination` DISABLE KEYS */;
/*!40000 ALTER TABLE `CandidateNomination` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CaseOfStudy`
--

DROP TABLE IF EXISTS `CaseOfStudy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CaseOfStudy` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LogoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LogoID` (`LogoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CaseOfStudy`
--

LOCK TABLES `CaseOfStudy` WRITE;
/*!40000 ALTER TABLE `CaseOfStudy` DISABLE KEYS */;
/*!40000 ALTER TABLE `CaseOfStudy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CertifiedOpenStackAdministratorExam`
--

DROP TABLE IF EXISTS `CertifiedOpenStackAdministratorExam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CertifiedOpenStackAdministratorExam` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CertifiedOpenStackAdministratorExam') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CertifiedOpenStackAdministratorExam',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ExternalID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExpirationDate` datetime DEFAULT NULL,
  `PassFailDate` datetime DEFAULT NULL,
  `ModifiedDate` datetime DEFAULT NULL,
  `Status` enum('None','New','Pending','Pass','No Pass','No Pending','Invalidated','Cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `Code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CertificationNumber` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CertificationStatus` enum('None','Achieved','InProgress','Expired','Renewed','In Appeals','Revoked') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `CertificationExpirationDate` datetime DEFAULT NULL,
  `TrackID` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TrackModifiedDate` datetime DEFAULT NULL,
  `CandidateName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CandidateNameFirstName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CandidateNameLastName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CandidateEmail` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CandidateExternalID` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CompletedDate` datetime DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CertifiedOpenStackAdministratorExam`
--

LOCK TABLES `CertifiedOpenStackAdministratorExam` WRITE;
/*!40000 ALTER TABLE `CertifiedOpenStackAdministratorExam` DISABLE KEYS */;
/*!40000 ALTER TABLE `CertifiedOpenStackAdministratorExam` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ChatTeam`
--

DROP TABLE IF EXISTS `ChatTeam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChatTeam` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ChatTeam') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ChatTeam',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChatTeam`
--

LOCK TABLES `ChatTeam` WRITE;
/*!40000 ALTER TABLE `ChatTeam` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChatTeam` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ChatTeamInvitation`
--

DROP TABLE IF EXISTS `ChatTeamInvitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChatTeamInvitation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ChatTeamInvitation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ChatTeamInvitation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Permission` enum('READ','WRITE','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'READ',
  `Accepted` tinyint unsigned NOT NULL DEFAULT '0',
  `AcceptedDate` datetime DEFAULT NULL,
  `InviterID` int DEFAULT NULL,
  `InviteeID` int DEFAULT NULL,
  `TeamID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `InviterID` (`InviterID`),
  KEY `InviteeID` (`InviteeID`),
  KEY `TeamID` (`TeamID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChatTeamInvitation`
--

LOCK TABLES `ChatTeamInvitation` WRITE;
/*!40000 ALTER TABLE `ChatTeamInvitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChatTeamInvitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ChatTeamPushNotificationMessage`
--

DROP TABLE IF EXISTS `ChatTeamPushNotificationMessage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChatTeamPushNotificationMessage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ChatTeamID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ChatTeamID` (`ChatTeamID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChatTeamPushNotificationMessage`
--

LOCK TABLES `ChatTeamPushNotificationMessage` WRITE;
/*!40000 ALTER TABLE `ChatTeamPushNotificationMessage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChatTeamPushNotificationMessage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ChatTeam_Members`
--

DROP TABLE IF EXISTS `ChatTeam_Members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ChatTeam_Members` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ChatTeamID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `Permission` enum('READ','WRITE','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'READ',
  PRIMARY KEY (`ID`),
  KEY `ChatTeamID` (`ChatTeamID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ChatTeam_Members`
--

LOCK TABLES `ChatTeam_Members` WRITE;
/*!40000 ALTER TABLE `ChatTeam_Members` DISABLE KEYS */;
/*!40000 ALTER TABLE `ChatTeam_Members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CloudImageCachedStore`
--

DROP TABLE IF EXISTS `CloudImageCachedStore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CloudImageCachedStore` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CloudImageCachedStore') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CloudImageCachedStore',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CloudStatus` enum('Local','Live','Error') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Local',
  `CloudSize` int NOT NULL DEFAULT '0',
  `CloudMetaJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SourceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SourceID` (`SourceID`),
  KEY `Filename` (`Filename`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CloudImageCachedStore`
--

LOCK TABLES `CloudImageCachedStore` WRITE;
/*!40000 ALTER TABLE `CloudImageCachedStore` DISABLE KEYS */;
/*!40000 ALTER TABLE `CloudImageCachedStore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CloudService`
--

DROP TABLE IF EXISTS `CloudService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CloudService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CloudService`
--

LOCK TABLES `CloudService` WRITE;
/*!40000 ALTER TABLE `CloudService` DISABLE KEYS */;
/*!40000 ALTER TABLE `CloudService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CloudServiceOffered`
--

DROP TABLE IF EXISTS `CloudServiceOffered`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CloudServiceOffered` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CloudServiceOffered`
--

LOCK TABLES `CloudServiceOffered` WRITE;
/*!40000 ALTER TABLE `CloudServiceOffered` DISABLE KEYS */;
/*!40000 ALTER TABLE `CloudServiceOffered` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CloudServiceOfferedDraft_PricingSchemas`
--

DROP TABLE IF EXISTS `CloudServiceOfferedDraft_PricingSchemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CloudServiceOfferedDraft_PricingSchemas` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CloudServiceOfferedDraftID` int NOT NULL DEFAULT '0',
  `PricingSchemaTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CloudServiceOfferedDraftID` (`CloudServiceOfferedDraftID`),
  KEY `PricingSchemaTypeID` (`PricingSchemaTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CloudServiceOfferedDraft_PricingSchemas`
--

LOCK TABLES `CloudServiceOfferedDraft_PricingSchemas` WRITE;
/*!40000 ALTER TABLE `CloudServiceOfferedDraft_PricingSchemas` DISABLE KEYS */;
/*!40000 ALTER TABLE `CloudServiceOfferedDraft_PricingSchemas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CloudServiceOffered_PricingSchemas`
--

DROP TABLE IF EXISTS `CloudServiceOffered_PricingSchemas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CloudServiceOffered_PricingSchemas` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CloudServiceOfferedID` int NOT NULL DEFAULT '0',
  `PricingSchemaTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CloudServiceOfferedID` (`CloudServiceOfferedID`),
  KEY `PricingSchemaTypeID` (`PricingSchemaTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CloudServiceOffered_PricingSchemas`
--

LOCK TABLES `CloudServiceOffered_PricingSchemas` WRITE;
/*!40000 ALTER TABLE `CloudServiceOffered_PricingSchemas` DISABLE KEYS */;
/*!40000 ALTER TABLE `CloudServiceOffered_PricingSchemas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommMember`
--

DROP TABLE IF EXISTS `CommMember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommMember` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CommMember') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CommMember',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CommPageID` int DEFAULT NULL,
  `PhotoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CommPageID` (`CommPageID`),
  KEY `PhotoID` (`PhotoID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommMember`
--

LOCK TABLES `CommMember` WRITE;
/*!40000 ALTER TABLE `CommMember` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommMember` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityAward`
--

DROP TABLE IF EXISTS `CommunityAward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityAward` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CommunityAward') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CommunityAward',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityAward`
--

LOCK TABLES `CommunityAward` WRITE;
/*!40000 ALTER TABLE `CommunityAward` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityAward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityContributor`
--

DROP TABLE IF EXISTS `CommunityContributor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityContributor` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CommunityContributor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CommunityContributor',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Awards` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityContributor`
--

LOCK TABLES `CommunityContributor` WRITE;
/*!40000 ALTER TABLE `CommunityContributor` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityContributor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityContributor_Awards`
--

DROP TABLE IF EXISTS `CommunityContributor_Awards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityContributor_Awards` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CommunityContributorID` int NOT NULL DEFAULT '0',
  `CommunityAwardID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CommunityContributorID` (`CommunityContributorID`),
  KEY `CommunityAwardID` (`CommunityAwardID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityContributor_Awards`
--

LOCK TABLES `CommunityContributor_Awards` WRITE;
/*!40000 ALTER TABLE `CommunityContributor_Awards` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityContributor_Awards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPage`
--

DROP TABLE IF EXISTS `CommunityPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopSection` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPage`
--

LOCK TABLES `CommunityPage` WRITE;
/*!40000 ALTER TABLE `CommunityPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPageBis`
--

DROP TABLE IF EXISTS `CommunityPageBis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPageBis` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopBanner` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPageBis`
--

LOCK TABLES `CommunityPageBis` WRITE;
/*!40000 ALTER TABLE `CommunityPageBis` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPageBis` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPageBis_Ambassadors`
--

DROP TABLE IF EXISTS `CommunityPageBis_Ambassadors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPageBis_Ambassadors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CommunityPageBisID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CommunityPageBisID` (`CommunityPageBisID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPageBis_Ambassadors`
--

LOCK TABLES `CommunityPageBis_Ambassadors` WRITE;
/*!40000 ALTER TABLE `CommunityPageBis_Ambassadors` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPageBis_Ambassadors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPageBis_CommunityManagers`
--

DROP TABLE IF EXISTS `CommunityPageBis_CommunityManagers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPageBis_CommunityManagers` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CommunityPageBisID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CommunityPageBisID` (`CommunityPageBisID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPageBis_CommunityManagers`
--

LOCK TABLES `CommunityPageBis_CommunityManagers` WRITE;
/*!40000 ALTER TABLE `CommunityPageBis_CommunityManagers` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPageBis_CommunityManagers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPageBis_Live`
--

DROP TABLE IF EXISTS `CommunityPageBis_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPageBis_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopBanner` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPageBis_Live`
--

LOCK TABLES `CommunityPageBis_Live` WRITE;
/*!40000 ALTER TABLE `CommunityPageBis_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPageBis_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPageBis_versions`
--

DROP TABLE IF EXISTS `CommunityPageBis_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPageBis_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `TopBanner` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPageBis_versions`
--

LOCK TABLES `CommunityPageBis_versions` WRITE;
/*!40000 ALTER TABLE `CommunityPageBis_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPageBis_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPage_Live`
--

DROP TABLE IF EXISTS `CommunityPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TopSection` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPage_Live`
--

LOCK TABLES `CommunityPage_Live` WRITE;
/*!40000 ALTER TABLE `CommunityPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CommunityPage_versions`
--

DROP TABLE IF EXISTS `CommunityPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CommunityPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `TopSection` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CommunityPage_versions`
--

LOCK TABLES `CommunityPage_versions` WRITE;
/*!40000 ALTER TABLE `CommunityPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `CommunityPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Company`
--

DROP TABLE IF EXISTS `Company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Company` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Company') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Company',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `URL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Featured` tinyint unsigned NOT NULL DEFAULT '0',
  `City` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Industry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Products` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Contributions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ContactEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberLevel` enum('Platinum','Gold','StartUp','Corporate','Mention','None') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `AdminEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `URLSegment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Color` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Overview` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Commitment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CommitmentAuthor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `isDeleted` tinyint unsigned NOT NULL DEFAULT '0',
  `CCLASigned` tinyint unsigned NOT NULL DEFAULT '0',
  `CCLADate` datetime DEFAULT NULL,
  `CompanyListPageID` int DEFAULT NULL,
  `LogoID` int DEFAULT NULL,
  `BigLogoID` int DEFAULT NULL,
  `SubmitterID` int DEFAULT NULL,
  `CompanyAdminID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyListPageID` (`CompanyListPageID`),
  KEY `LogoID` (`LogoID`),
  KEY `BigLogoID` (`BigLogoID`),
  KEY `SubmitterID` (`SubmitterID`),
  KEY `CompanyAdminID` (`CompanyAdminID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=3981 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Company`
--

LOCK TABLES `Company` WRITE;
/*!40000 ALTER TABLE `Company` DISABLE KEYS */;
/*!40000 ALTER TABLE `Company` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyListPage_Donors`
--

DROP TABLE IF EXISTS `CompanyListPage_Donors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyListPage_Donors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CompanyListPageID` int NOT NULL DEFAULT '0',
  `CompanyID` int NOT NULL DEFAULT '0',
  `SortOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CompanyListPageID` (`CompanyListPageID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyListPage_Donors`
--

LOCK TABLES `CompanyListPage_Donors` WRITE;
/*!40000 ALTER TABLE `CompanyListPage_Donors` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyListPage_Donors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyService`
--

DROP TABLE IF EXISTS `CompanyService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CompanyService','RegionalSupportedCompanyService','OpenStackImplementation','Appliance','Distribution','CloudService','PrivateCloudService','PublicCloudService','RemoteCloudService','Consultant','TrainingService') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CompanyService',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Overview` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Call2ActionUri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `CompanyID` int DEFAULT NULL,
  `MarketPlaceTypeID` int DEFAULT NULL,
  `EditedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Company_Name_Class` (`Name`,`CompanyID`,`ClassName`),
  KEY `CompanyID` (`CompanyID`),
  KEY `MarketPlaceTypeID` (`MarketPlaceTypeID`),
  KEY `EditedByID` (`EditedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyService`
--

LOCK TABLES `CompanyService` WRITE;
/*!40000 ALTER TABLE `CompanyService` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyServiceDraft`
--

DROP TABLE IF EXISTS `CompanyServiceDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyServiceDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CompanyServiceDraft','RegionalSupportedCompanyServiceDraft','OpenStackImplementationDraft','ApplianceDraft','DistributionDraft','CloudServiceDraft','PrivateCloudServiceDraft','PublicCloudServiceDraft','RemoteCloudServiceDraft','ConsultantDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CompanyServiceDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Overview` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Call2ActionUri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `Published` tinyint unsigned NOT NULL DEFAULT '0',
  `LiveServiceID` int DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  `MarketPlaceTypeID` int DEFAULT NULL,
  `EditedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Company_Name_Class` (`Name`,`CompanyID`,`ClassName`),
  KEY `LiveServiceID` (`LiveServiceID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `MarketPlaceTypeID` (`MarketPlaceTypeID`),
  KEY `EditedByID` (`EditedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyServiceDraft`
--

LOCK TABLES `CompanyServiceDraft` WRITE;
/*!40000 ALTER TABLE `CompanyServiceDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyServiceDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyServiceResource`
--

DROP TABLE IF EXISTS `CompanyServiceResource`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyServiceResource` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CompanyServiceResource') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CompanyServiceResource',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Uri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Owner_Name` (`Name`,`OwnerID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyServiceResource`
--

LOCK TABLES `CompanyServiceResource` WRITE;
/*!40000 ALTER TABLE `CompanyServiceResource` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyServiceResource` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyServiceResourceDraft`
--

DROP TABLE IF EXISTS `CompanyServiceResourceDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyServiceResourceDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CompanyServiceResourceDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CompanyServiceResourceDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Uri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Owner_Name` (`Name`,`OwnerID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyServiceResourceDraft`
--

LOCK TABLES `CompanyServiceResourceDraft` WRITE;
/*!40000 ALTER TABLE `CompanyServiceResourceDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyServiceResourceDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CompanyServiceUpdateRecord`
--

DROP TABLE IF EXISTS `CompanyServiceUpdateRecord`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CompanyServiceUpdateRecord` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CompanyServiceUpdateRecord') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CompanyServiceUpdateRecord',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `CompanyServiceID` int DEFAULT NULL,
  `EditorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyServiceID` (`CompanyServiceID`),
  KEY `EditorID` (`EditorID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CompanyServiceUpdateRecord`
--

LOCK TABLES `CompanyServiceUpdateRecord` WRITE;
/*!40000 ALTER TABLE `CompanyServiceUpdateRecord` DISABLE KEYS */;
/*!40000 ALTER TABLE `CompanyServiceUpdateRecord` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Company_Administrators`
--

DROP TABLE IF EXISTS `Company_Administrators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Company_Administrators` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CompanyID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Company_Administrators`
--

LOCK TABLES `Company_Administrators` WRITE;
/*!40000 ALTER TABLE `Company_Administrators` DISABLE KEYS */;
/*!40000 ALTER TABLE `Company_Administrators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceLivePage`
--

DROP TABLE IF EXISTS `ConferenceLivePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceLivePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceLivePage`
--

LOCK TABLES `ConferenceLivePage` WRITE;
/*!40000 ALTER TABLE `ConferenceLivePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceLivePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceLivePage_Live`
--

DROP TABLE IF EXISTS `ConferenceLivePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceLivePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceLivePage_Live`
--

LOCK TABLES `ConferenceLivePage_Live` WRITE;
/*!40000 ALTER TABLE `ConferenceLivePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceLivePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceLivePage_versions`
--

DROP TABLE IF EXISTS `ConferenceLivePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceLivePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceLivePage_versions`
--

LOCK TABLES `ConferenceLivePage_versions` WRITE;
/*!40000 ALTER TABLE `ConferenceLivePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceLivePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferencePage`
--

DROP TABLE IF EXISTS `ConferencePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferencePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderArea` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeadlineSponsors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBCurrency` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `SummitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `SummitImageID` (`SummitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferencePage`
--

LOCK TABLES `ConferencePage` WRITE;
/*!40000 ALTER TABLE `ConferencePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferencePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferencePage_Live`
--

DROP TABLE IF EXISTS `ConferencePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferencePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderArea` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeadlineSponsors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBCurrency` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `SummitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `SummitImageID` (`SummitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferencePage_Live`
--

LOCK TABLES `ConferencePage_Live` WRITE;
/*!40000 ALTER TABLE `ConferencePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferencePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferencePage_versions`
--

DROP TABLE IF EXISTS `ConferencePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferencePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `HeaderArea` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeadlineSponsors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FBCurrency` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `SummitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `SummitID` (`SummitID`),
  KEY `SummitImageID` (`SummitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferencePage_versions`
--

LOCK TABLES `ConferencePage_versions` WRITE;
/*!40000 ALTER TABLE `ConferencePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferencePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceSubPage`
--

DROP TABLE IF EXISTS `ConferenceSubPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceSubPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HideSideBar` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceSubPage`
--

LOCK TABLES `ConferenceSubPage` WRITE;
/*!40000 ALTER TABLE `ConferenceSubPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceSubPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceSubPage_Live`
--

DROP TABLE IF EXISTS `ConferenceSubPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceSubPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HideSideBar` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceSubPage_Live`
--

LOCK TABLES `ConferenceSubPage_Live` WRITE;
/*!40000 ALTER TABLE `ConferenceSubPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceSubPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConferenceSubPage_versions`
--

DROP TABLE IF EXISTS `ConferenceSubPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConferenceSubPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `HideSideBar` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConferenceSubPage_versions`
--

LOCK TABLES `ConferenceSubPage_versions` WRITE;
/*!40000 ALTER TABLE `ConferenceSubPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConferenceSubPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConfigurationManagementType`
--

DROP TABLE IF EXISTS `ConfigurationManagementType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConfigurationManagementType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ConfigurationManagementType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ConfigurationManagementType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConfigurationManagementType`
--

LOCK TABLES `ConfigurationManagementType` WRITE;
/*!40000 ALTER TABLE `ConfigurationManagementType` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConfigurationManagementType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Consultant`
--

DROP TABLE IF EXISTS `Consultant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Consultant` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Consultant`
--

LOCK TABLES `Consultant` WRITE;
/*!40000 ALTER TABLE `Consultant` DISABLE KEYS */;
/*!40000 ALTER TABLE `Consultant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantClient`
--

DROP TABLE IF EXISTS `ConsultantClient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantClient` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ConsultantClient') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ConsultantClient',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `ConsultantID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name_Owner` (`Name`,`ConsultantID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantClient`
--

LOCK TABLES `ConsultantClient` WRITE;
/*!40000 ALTER TABLE `ConsultantClient` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantClient` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantClientDraft`
--

DROP TABLE IF EXISTS `ConsultantClientDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantClientDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ConsultantClientDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ConsultantClientDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `ConsultantID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name_Owner` (`Name`,`ConsultantID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantClientDraft`
--

LOCK TABLES `ConsultantClientDraft` WRITE;
/*!40000 ALTER TABLE `ConsultantClientDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantClientDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantDraft_ConfigurationManagementExpertises`
--

DROP TABLE IF EXISTS `ConsultantDraft_ConfigurationManagementExpertises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantDraft_ConfigurationManagementExpertises` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantDraftID` int NOT NULL DEFAULT '0',
  `ConfigurationManagementTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantDraftID` (`ConsultantDraftID`),
  KEY `ConfigurationManagementTypeID` (`ConfigurationManagementTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantDraft_ConfigurationManagementExpertises`
--

LOCK TABLES `ConsultantDraft_ConfigurationManagementExpertises` WRITE;
/*!40000 ALTER TABLE `ConsultantDraft_ConfigurationManagementExpertises` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantDraft_ConfigurationManagementExpertises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantDraft_ExpertiseAreas`
--

DROP TABLE IF EXISTS `ConsultantDraft_ExpertiseAreas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantDraft_ExpertiseAreas` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantDraftID` int NOT NULL DEFAULT '0',
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantDraftID` (`ConsultantDraftID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantDraft_ExpertiseAreas`
--

LOCK TABLES `ConsultantDraft_ExpertiseAreas` WRITE;
/*!40000 ALTER TABLE `ConsultantDraft_ExpertiseAreas` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantDraft_ExpertiseAreas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantDraft_ServicesOffered`
--

DROP TABLE IF EXISTS `ConsultantDraft_ServicesOffered`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantDraft_ServicesOffered` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantDraftID` int NOT NULL DEFAULT '0',
  `ConsultantServiceOfferedTypeID` int NOT NULL DEFAULT '0',
  `RegionID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantDraftID` (`ConsultantDraftID`),
  KEY `ConsultantServiceOfferedTypeID` (`ConsultantServiceOfferedTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantDraft_ServicesOffered`
--

LOCK TABLES `ConsultantDraft_ServicesOffered` WRITE;
/*!40000 ALTER TABLE `ConsultantDraft_ServicesOffered` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantDraft_ServicesOffered` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantDraft_SpokenLanguages`
--

DROP TABLE IF EXISTS `ConsultantDraft_SpokenLanguages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantDraft_SpokenLanguages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantDraftID` int NOT NULL DEFAULT '0',
  `SpokenLanguageID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantDraftID` (`ConsultantDraftID`),
  KEY `SpokenLanguageID` (`SpokenLanguageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantDraft_SpokenLanguages`
--

LOCK TABLES `ConsultantDraft_SpokenLanguages` WRITE;
/*!40000 ALTER TABLE `ConsultantDraft_SpokenLanguages` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantDraft_SpokenLanguages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ConsultantServiceOfferedType`
--

DROP TABLE IF EXISTS `ConsultantServiceOfferedType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ConsultantServiceOfferedType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ConsultantServiceOfferedType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ConsultantServiceOfferedType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ConsultantServiceOfferedType`
--

LOCK TABLES `ConsultantServiceOfferedType` WRITE;
/*!40000 ALTER TABLE `ConsultantServiceOfferedType` DISABLE KEYS */;
/*!40000 ALTER TABLE `ConsultantServiceOfferedType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Consultant_ConfigurationManagementExpertises`
--

DROP TABLE IF EXISTS `Consultant_ConfigurationManagementExpertises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Consultant_ConfigurationManagementExpertises` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantID` int NOT NULL DEFAULT '0',
  `ConfigurationManagementTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ConfigurationManagementTypeID` (`ConfigurationManagementTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Consultant_ConfigurationManagementExpertises`
--

LOCK TABLES `Consultant_ConfigurationManagementExpertises` WRITE;
/*!40000 ALTER TABLE `Consultant_ConfigurationManagementExpertises` DISABLE KEYS */;
/*!40000 ALTER TABLE `Consultant_ConfigurationManagementExpertises` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Consultant_ExpertiseAreas`
--

DROP TABLE IF EXISTS `Consultant_ExpertiseAreas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Consultant_ExpertiseAreas` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantID` int NOT NULL DEFAULT '0',
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Consultant_ExpertiseAreas`
--

LOCK TABLES `Consultant_ExpertiseAreas` WRITE;
/*!40000 ALTER TABLE `Consultant_ExpertiseAreas` DISABLE KEYS */;
/*!40000 ALTER TABLE `Consultant_ExpertiseAreas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Consultant_ServicesOffered`
--

DROP TABLE IF EXISTS `Consultant_ServicesOffered`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Consultant_ServicesOffered` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantID` int NOT NULL DEFAULT '0',
  `ConsultantServiceOfferedTypeID` int NOT NULL DEFAULT '0',
  `RegionID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ConsultantServiceOfferedTypeID` (`ConsultantServiceOfferedTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Consultant_ServicesOffered`
--

LOCK TABLES `Consultant_ServicesOffered` WRITE;
/*!40000 ALTER TABLE `Consultant_ServicesOffered` DISABLE KEYS */;
/*!40000 ALTER TABLE `Consultant_ServicesOffered` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Consultant_SpokenLanguages`
--

DROP TABLE IF EXISTS `Consultant_SpokenLanguages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Consultant_SpokenLanguages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ConsultantID` int NOT NULL DEFAULT '0',
  `SpokenLanguageID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `SpokenLanguageID` (`SpokenLanguageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Consultant_SpokenLanguages`
--

LOCK TABLES `Consultant_SpokenLanguages` WRITE;
/*!40000 ALTER TABLE `Consultant_SpokenLanguages` DISABLE KEYS */;
/*!40000 ALTER TABLE `Consultant_SpokenLanguages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Continent`
--

DROP TABLE IF EXISTS `Continent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Continent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Continent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Continent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Continent`
--

LOCK TABLES `Continent` WRITE;
/*!40000 ALTER TABLE `Continent` DISABLE KEYS */;
/*!40000 ALTER TABLE `Continent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Contract`
--

DROP TABLE IF EXISTS `Contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Contract` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Contract') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Contract',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ContractSigned` tinyint unsigned NOT NULL DEFAULT '0',
  `ContractStart` date DEFAULT NULL,
  `ContractEnd` date DEFAULT NULL,
  `EchosignID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompanyID` int DEFAULT NULL,
  `ContractTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ContractTemplateID` (`ContractTemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Contract`
--

LOCK TABLES `Contract` WRITE;
/*!40000 ALTER TABLE `Contract` DISABLE KEYS */;
/*!40000 ALTER TABLE `Contract` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ContractTemplate`
--

DROP TABLE IF EXISTS `ContractTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ContractTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ContractTemplate','MarketplaceContractTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ContractTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Duration` int NOT NULL DEFAULT '0',
  `AutoRenew` tinyint unsigned NOT NULL DEFAULT '0',
  `PDFID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PDFID` (`PDFID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ContractTemplate`
--

LOCK TABLES `ContractTemplate` WRITE;
/*!40000 ALTER TABLE `ContractTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `ContractTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ContributorsIngestRequest`
--

DROP TABLE IF EXISTS `ContributorsIngestRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ContributorsIngestRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ContributorsIngestRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ContributorsIngestRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `IsRunning` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ContributorsIngestRequest`
--

LOCK TABLES `ContributorsIngestRequest` WRITE;
/*!40000 ALTER TABLE `ContributorsIngestRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `ContributorsIngestRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CustomerCaseStudy`
--

DROP TABLE IF EXISTS `CustomerCaseStudy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CustomerCaseStudy` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CustomerCaseStudy') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CustomerCaseStudy',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Uri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Owner_Name` (`Name`,`OwnerID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CustomerCaseStudy`
--

LOCK TABLES `CustomerCaseStudy` WRITE;
/*!40000 ALTER TABLE `CustomerCaseStudy` DISABLE KEYS */;
/*!40000 ALTER TABLE `CustomerCaseStudy` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `CustomerCaseStudyDraft`
--

DROP TABLE IF EXISTS `CustomerCaseStudyDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `CustomerCaseStudyDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('CustomerCaseStudyDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CustomerCaseStudyDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Uri` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Owner_Name` (`Name`,`OwnerID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `CustomerCaseStudyDraft`
--

LOCK TABLES `CustomerCaseStudyDraft` WRITE;
/*!40000 ALTER TABLE `CustomerCaseStudyDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `CustomerCaseStudyDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DataCenterLocation`
--

DROP TABLE IF EXISTS `DataCenterLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DataCenterLocation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DataCenterLocation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DataCenterLocation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `City` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  `CloudServiceID` int DEFAULT NULL,
  `DataCenterRegionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `City_State_Country_Service_Region` (`CloudServiceID`,`DataCenterRegionID`,`City`,`Country`,`State`),
  KEY `CloudServiceID` (`CloudServiceID`),
  KEY `DataCenterRegionID` (`DataCenterRegionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DataCenterLocation`
--

LOCK TABLES `DataCenterLocation` WRITE;
/*!40000 ALTER TABLE `DataCenterLocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `DataCenterLocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DataCenterLocationDraft`
--

DROP TABLE IF EXISTS `DataCenterLocationDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DataCenterLocationDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DataCenterLocationDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DataCenterLocationDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `City` varchar(125) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  `CloudServiceID` int DEFAULT NULL,
  `DataCenterRegionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `City_State_Country_Service_Region` (`CloudServiceID`,`DataCenterRegionID`,`City`,`Country`,`State`),
  KEY `CloudServiceID` (`CloudServiceID`),
  KEY `DataCenterRegionID` (`DataCenterRegionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DataCenterLocationDraft`
--

LOCK TABLES `DataCenterLocationDraft` WRITE;
/*!40000 ALTER TABLE `DataCenterLocationDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `DataCenterLocationDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DataCenterRegion`
--

DROP TABLE IF EXISTS `DataCenterRegion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DataCenterRegion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DataCenterRegion') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DataCenterRegion',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Endpoint` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Color` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CloudServiceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CloudServiceID` (`CloudServiceID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DataCenterRegion`
--

LOCK TABLES `DataCenterRegion` WRITE;
/*!40000 ALTER TABLE `DataCenterRegion` DISABLE KEYS */;
/*!40000 ALTER TABLE `DataCenterRegion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DataCenterRegionDraft`
--

DROP TABLE IF EXISTS `DataCenterRegionDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DataCenterRegionDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DataCenterRegionDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DataCenterRegionDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Endpoint` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Color` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CloudServiceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CloudServiceID` (`CloudServiceID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DataCenterRegionDraft`
--

LOCK TABLES `DataCenterRegionDraft` WRITE;
/*!40000 ALTER TABLE `DataCenterRegionDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `DataCenterRegionDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DefaultPresentationType`
--

DROP TABLE IF EXISTS `DefaultPresentationType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DefaultPresentationType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MaxSpeakers` int NOT NULL DEFAULT '0',
  `MinSpeakers` int NOT NULL DEFAULT '0',
  `MaxModerators` int NOT NULL DEFAULT '0',
  `MinModerators` int NOT NULL DEFAULT '0',
  `UseSpeakers` tinyint unsigned NOT NULL DEFAULT '0',
  `AreSpeakersMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `UseModerator` tinyint unsigned NOT NULL DEFAULT '0',
  `IsModeratorMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ModeratorLabel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShouldBeAvailableOnCFP` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DefaultPresentationType`
--

LOCK TABLES `DefaultPresentationType` WRITE;
/*!40000 ALTER TABLE `DefaultPresentationType` DISABLE KEYS */;
/*!40000 ALTER TABLE `DefaultPresentationType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DefaultSummitEventType`
--

DROP TABLE IF EXISTS `DefaultSummitEventType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DefaultSummitEventType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DefaultSummitEventType','DefaultPresentationType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DefaultSummitEventType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Color` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BlackoutTimes` tinyint unsigned NOT NULL DEFAULT '0',
  `UseSponsors` tinyint unsigned NOT NULL DEFAULT '0',
  `AreSponsorsMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `AllowsAttachment` tinyint unsigned NOT NULL DEFAULT '0',
  `IsPrivate` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DefaultSummitEventType`
--

LOCK TABLES `DefaultSummitEventType` WRITE;
/*!40000 ALTER TABLE `DefaultSummitEventType` DISABLE KEYS */;
/*!40000 ALTER TABLE `DefaultSummitEventType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DefaultTrackTagGroup`
--

DROP TABLE IF EXISTS `DefaultTrackTagGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DefaultTrackTagGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DefaultTrackTagGroup') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DefaultTrackTagGroup',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Mandatory` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DefaultTrackTagGroup`
--

LOCK TABLES `DefaultTrackTagGroup` WRITE;
/*!40000 ALTER TABLE `DefaultTrackTagGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `DefaultTrackTagGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DefaultTrackTagGroup_AllowedTags`
--

DROP TABLE IF EXISTS `DefaultTrackTagGroup_AllowedTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DefaultTrackTagGroup_AllowedTags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `DefaultTrackTagGroupID` int NOT NULL DEFAULT '0',
  `TagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `DefaultTrackTagGroupID` (`DefaultTrackTagGroupID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_DefaultTrackTagGroup_AllowedTags_DefaultTrackTagGroup` FOREIGN KEY (`DefaultTrackTagGroupID`) REFERENCES `DefaultTrackTagGroup` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_DefaultTrackTagGroup_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DefaultTrackTagGroup_AllowedTags`
--

LOCK TABLES `DefaultTrackTagGroup_AllowedTags` WRITE;
/*!40000 ALTER TABLE `DefaultTrackTagGroup_AllowedTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `DefaultTrackTagGroup_AllowedTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DeletedDupeMember`
--

DROP TABLE IF EXISTS `DeletedDupeMember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DeletedDupeMember` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DeletedDupeMember') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DeletedDupeMember',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MemberID` int NOT NULL DEFAULT '0',
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Password` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PasswordEncryption` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Salt` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PasswordExpiry` date DEFAULT NULL,
  `LockedOutUntil` datetime DEFAULT NULL,
  `Locale` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DateFormat` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TimeFormat` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SecondEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ThirdEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HasBeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `ShirtSize` enum('Extra Small','Small','Medium','Large','XL','XXL') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Extra Small',
  `StatementOfInterest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FoodPreference` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherFood` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IRCHandle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Projects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherProject` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubscribedToNewsletter` tinyint unsigned NOT NULL DEFAULT '0',
  `JobTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Role` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LinkedInProfile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Suburb` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Postcode` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `City` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Gender` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TypeOfDirector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DeletedDupeMember`
--

LOCK TABLES `DeletedDupeMember` WRITE;
/*!40000 ALTER TABLE `DeletedDupeMember` DISABLE KEYS */;
/*!40000 ALTER TABLE `DeletedDupeMember` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Deployment`
--

DROP TABLE IF EXISTS `Deployment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Deployment` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Deployment') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Deployment',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsPublic` tinyint unsigned NOT NULL DEFAULT '0',
  `DeploymentType` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProjectsUsed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentReleases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DeploymentStage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NumCloudUsers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WorkloadsDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherWorkloadsDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `APIFormats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Hypervisors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherHypervisor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BlockStorageDrivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherBlockStorageDriver` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NetworkDrivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherNetworkDriver` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhyNovaNetwork` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherWhyNovaNetwork` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IdentityDrivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherIndentityDriver` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SupportedFeatures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DeploymentTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherDeploymentTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherOperatingSystems` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ComputeNodes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ComputeCores` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ComputeInstances` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BlockStorageTotalSize` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ObjectStorageSize` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ObjectStorageNumObjects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NetworkNumIPs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SendDigest` tinyint unsigned NOT NULL DEFAULT '0',
  `UpdateDate` datetime DEFAULT NULL,
  `SwiftGlobalDistributionFeatures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SwiftGlobalDistributionFeaturesUsesCases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherSwiftGlobalDistributionFeaturesUsesCases` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Plans2UseSwiftStoragePolicies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherPlans2UseSwiftStoragePolicies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `UsedDBForOpenStackComponents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherUsedDBForOpenStackComponents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolsUsedForYourUsers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherToolsUsedForYourUsers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Reason2Move2Ceilometer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CountriesPhysicalLocation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CountriesUsersLocation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ServicesDeploymentsWorkloads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherServicesDeploymentsWorkloads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EnterpriseDeploymentsWorkloads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherEnterpriseDeploymentsWorkloads` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HorizontalWorkloadFrameworks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherHorizontalWorkloadFrameworks` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `UsedPackages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CustomPackagesReason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherCustomPackagesReason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PaasTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherPaasTools` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherSupportedFeatures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InteractingClouds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherInteractingClouds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DeploymentSurveyID` int DEFAULT NULL,
  `OrgID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DeploymentSurveyID` (`DeploymentSurveyID`),
  KEY `OrgID` (`OrgID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Deployment`
--

LOCK TABLES `Deployment` WRITE;
/*!40000 ALTER TABLE `Deployment` DISABLE KEYS */;
/*!40000 ALTER TABLE `Deployment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DeploymentSurvey`
--

DROP TABLE IF EXISTS `DeploymentSurvey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DeploymentSurvey` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DeploymentSurvey') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DeploymentSurvey',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Industry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherIndustry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PrimaryCity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PrimaryState` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PrimaryCountry` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OrgSize` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackInvolvement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InformationSources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherInformationSources` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FurtherEnhancement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FoundationUserCommitteePriorities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BusinessDrivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherBusinessDrivers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhatDoYouLikeMost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `UserGroupMember` tinyint unsigned NOT NULL DEFAULT '0',
  `UserGroupName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentStep` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HighestStepAllowed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `OkToContact` tinyint unsigned NOT NULL DEFAULT '0',
  `SendDigest` tinyint unsigned NOT NULL DEFAULT '0',
  `UpdateDate` datetime DEFAULT NULL,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Surname` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackRecommendRate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackRecommendation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackActivity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackRelationship` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ITActivity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InterestedUsingContainerTechnology` tinyint unsigned NOT NULL DEFAULT '0',
  `ContainerRelatedTechnologies` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  `OrgID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `OrgID` (`OrgID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DeploymentSurvey`
--

LOCK TABLES `DeploymentSurvey` WRITE;
/*!40000 ALTER TABLE `DeploymentSurvey` DISABLE KEYS */;
/*!40000 ALTER TABLE `DeploymentSurvey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Distribution`
--

DROP TABLE IF EXISTS `Distribution`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Distribution` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Distribution`
--

LOCK TABLES `Distribution` WRITE;
/*!40000 ALTER TABLE `Distribution` DISABLE KEYS */;
/*!40000 ALTER TABLE `Distribution` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DoctrineMigration`
--

DROP TABLE IF EXISTS `DoctrineMigration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DoctrineMigration` (
  `version` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `executed_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DoctrineMigration`
--

LOCK TABLES `DoctrineMigration` WRITE;
/*!40000 ALTER TABLE `DoctrineMigration` DISABLE KEYS */;
INSERT INTO `DoctrineMigration` VALUES ('20190422151949',NULL),('20190506153014',NULL),('20190506153909',NULL),('20190529015655',NULL),('20190529142913',NULL),('20190529142927',NULL),('20190530205326',NULL),('20190530205344',NULL),('20190625030955',NULL),('20190626125814',NULL),('20190629222739',NULL),('20190723210551',NULL),('20190728200547',NULL),('20190824125218',NULL),('20190730022151',NULL),('20190730031422',NULL),('20190801211505',NULL),('20190911132806',NULL),('20190918111958',NULL),('20191016014630',NULL),('20191202223721',NULL),('20191212002736',NULL),('20191220223248',NULL),('20191220223253',NULL),('20191224021722',NULL),('20191224022307',NULL),('20191229173636',NULL),('20200109171923',NULL),('20200110184019',NULL),('20191116183316',NULL),('20191125210134',NULL),('20191206163423',NULL),('20200123133515',NULL),('20200212023535',NULL),('20200212125943',NULL),('20200213131907',NULL),('20200128184149',NULL),('20200128191140',NULL),('20200403191418',NULL),('20200512132942',NULL),('20200512174027',NULL),('20200523235306',NULL),('20200526174904',NULL),('20200601211446',NULL),('20200602212951',NULL),('20200609105105',NULL),('20200616144713',NULL),('20200618192655',NULL),('20200623191130',NULL),('20200623191331',NULL),('20200623191754',NULL),('20200624132001',NULL),('20200629142643',NULL),('20200629143447',NULL),('20200730135823',NULL),('20200803171455',NULL),('20200713164340',NULL),('20200713164344',NULL),('20200817180752',NULL),('20200818120409',NULL),('20200824140528',NULL),('20200831193516',NULL),('20200901160152',NULL),('20200904155247',NULL),('20200910184756',NULL),('20200924123949',NULL),('20200924203451',NULL),('20200924210244',NULL),('20200928132323',NULL),('20201001182314',NULL),('20201008203936',NULL),('20201014155708',NULL),('20201014155719',NULL),('20201014161727',NULL),('20201015153512',NULL),('20201015153514',NULL),('20201015153516',NULL),('20201016145706',NULL),('20201018045210',NULL),('20201021125624',NULL),('20201021172434',NULL),('20201022181641',NULL),('20201027024056',NULL),('20201029175540',NULL),('20201116151153',NULL),('20201119155826',NULL),('20201120143925',NULL),('20201208150500',NULL),('20201208151735',NULL),('20210203161916','2021-04-16 01:48:36'),('20210212151954','2021-04-16 01:48:36'),('20210212151956','2021-04-16 01:48:37'),('20210322170708','2021-04-16 01:48:37'),('20210326171114','2021-04-16 01:48:49'),('20210326171117','2021-04-16 01:48:49'),('20210405144636','2021-04-16 01:48:58'),('20210406124904','2021-04-16 01:48:58'),('20210406125358','2021-04-16 01:49:05'),('20210416191958','2021-04-24 01:55:34'),('20210419181056','2021-04-24 01:55:42'),('20210422150202','2021-04-24 01:55:50'),('20210426223306','2021-04-28 02:10:11'),('20210429160901','2021-04-29 21:21:22'),('20210521135639','2021-06-03 17:09:34'),('20210521135642','2021-06-03 17:09:34'),('20210521170713','2021-06-03 17:09:38'),('20210528150223','2021-06-03 17:09:47'),('20210602181838','2021-06-03 17:09:48'),('20210603182544','2021-06-03 18:50:19'),('20210601152355','2021-06-09 19:32:06'),('20210628184207','2021-06-29 19:26:29'),('20210707172103','2021-07-15 12:13:12'),('20210707172106','2021-07-15 12:13:13'),('20210716165815','2021-07-19 13:01:11'),('20210816174116','2021-08-16 17:48:08'),('20210826171650','2021-08-30 22:47:53'),('20210903180455','2021-09-15 22:09:01'),('20210903182620','2021-09-15 22:09:01'),('20210913203442','2021-09-15 22:09:02'),('20210913215613','2021-09-15 22:09:02'),('20210913215614','2021-09-15 22:09:03'),('20211006122424','2021-10-14 00:30:11'),('20211006122426','2021-10-14 00:30:11'),('20211007161147','2021-10-14 00:30:19'),('20211013164919','2021-10-14 00:30:27'),('20211014140751','2021-10-18 17:53:16'),('20211018134022','2021-10-18 17:58:06'),('20211103124532','2021-11-03 13:11:43'),('20211007133152','2021-11-16 00:01:52'),('20211012162726','2021-11-16 00:02:00'),('20211112190853','2021-11-16 00:02:01'),('20211129183414','2021-11-29 23:31:24'),('20211213135926','2021-12-15 10:49:33'),('20220106085440','2022-03-01 15:52:08'),('20220111214358','2022-03-01 15:52:17'),('20220125200224','2022-03-01 15:52:26'),('20220127210145','2022-03-01 15:52:35'),('20220127210146','2022-03-01 15:52:44'),('20220128194504','2022-03-01 15:52:53'),('20220128200351','2022-03-01 15:53:02'),('20220131195047','2022-03-01 15:53:11'),('20220131201421','2022-03-01 15:53:11'),('20220204152158','2022-03-01 15:53:20'),('20220207183947','2022-03-01 15:53:30'),('20220207183951','2022-03-01 15:53:30'),('20220207195239','2022-03-01 15:53:30'),('20220207195617','2022-03-01 15:55:30'),('20220210181934','2022-03-01 15:55:40'),('20220210181935','2022-03-01 15:55:40'),('20220214140659','2022-03-01 15:55:49'),('20220215210214','2022-03-01 15:55:49'),('20220216140653','2022-03-01 15:55:49'),('20220216144229','2022-03-01 15:55:49'),('20220216213443','2022-03-01 15:55:57'),('20220218124421','2022-03-01 15:55:58'),('20220223221730','2022-03-01 15:55:58'),('20220314152133','2022-03-30 02:31:47'),('20220322141015','2022-03-30 02:31:56'),('20220322195257','2022-03-30 02:32:05'),('20220328214032','2022-03-30 02:32:13'),('20220328170502','2022-04-19 02:08:41'),('20220330180247','2022-04-19 02:08:50'),('20220331173736','2022-04-19 02:08:59'),('20220404193539','2022-04-19 02:09:08'),('20220405205916','2022-04-19 02:09:08'),('20220405205925','2022-04-19 02:09:17'),('20220406133959','2022-04-19 02:09:27'),('20220406141529','2022-04-19 02:09:27'),('20220412182357','2022-04-19 02:09:27'),('20220418172350','2022-05-04 02:07:18'),('20220418192910','2022-05-04 02:07:27'),('20220420155435','2022-05-04 02:07:36'),('20220420171938','2022-05-04 02:07:36'),('20220420171940','2022-05-04 02:07:37'),('20220420184724','2022-05-04 02:07:37'),('20220427192118','2022-05-04 02:07:46'),('20220427203735','2022-05-04 02:07:55'),('20220503185119','2022-05-04 02:07:56'),('20220512193453','2022-05-24 19:18:18'),('20220421184853','2022-06-07 08:34:10'),('20220421184854','2022-06-07 08:34:19'),('20220421184855','2022-06-07 08:34:19'),('20220506190146','2022-06-07 08:34:28'),('20220506190147','2022-06-07 08:34:37'),('20220506190148','2022-06-07 08:34:47'),('20220518162847','2022-06-07 08:34:48'),('20220620181650','2022-07-07 12:03:32'),('20220620181652','2022-07-07 12:03:41'),('20220620182703','2022-07-07 12:03:41'),('20220621150711','2022-07-07 12:03:41'),('20220622172244','2022-07-07 12:03:41'),('20220622172245','2022-07-07 12:03:42'),('20220629180748','2022-07-14 19:21:10'),('20220630132018','2022-07-14 19:21:10'),('20220708155017','2022-07-14 19:21:20'),('20220708155018','2022-07-14 19:21:20'),('20220711210718','2022-07-14 19:21:29'),('20220705184048','2022-07-15 21:13:28'),('20220720125644','2022-07-20 15:07:50'),('20220718214726','2022-08-08 15:38:02'),('20220720202650','2022-08-08 15:38:12'),('20220720202655','2022-08-08 15:38:22'),('20220722142231','2022-08-08 15:38:32'),('20220726221639','2022-08-08 15:38:32'),('20220726224823','2022-08-08 15:38:32'),('20220728135232','2022-08-08 15:38:33'),('20220802211331','2022-08-08 15:38:43'),('20220809200051','2022-08-29 17:03:55'),('20220812151207','2022-08-29 17:04:06'),('20220815160210','2022-08-29 17:04:25'),('20220815160211','2022-08-29 17:04:25'),('20220830094421','2022-08-30 10:13:15'),('20220830094423','2022-08-30 10:13:17'),('20220926172743','2022-09-26 18:08:10'),('20221004141001','2022-12-01 13:11:04'),('20221012133111','2022-12-01 13:11:08'),('20220926134809','2022-12-01 14:05:12'),('20220926134810','2022-12-01 14:05:13'),('20220927152214','2022-12-01 14:05:13'),('20221215191405','2023-01-10 14:30:42'),('20220830224755','2023-02-01 17:17:14'),('20220901205619','2023-02-01 17:17:25'),('20220914190758','2023-02-01 17:17:25'),('20221031172853','2023-02-01 17:17:35'),('20221031174604','2023-02-01 17:17:35'),('20221031174714','2023-02-01 17:17:45'),('20221031181921','2023-02-01 17:17:45'),('20221031182022','2023-02-01 17:17:55'),('20221101142824','2023-02-01 17:18:06'),('20221101150556','2023-02-01 17:18:16'),('20221101150558','2023-02-01 17:18:26'),('20221101154408','2023-02-01 17:18:36'),('20221101154409','2023-02-01 17:18:46'),('20221101163314','2023-02-01 17:18:46'),('20221101180646','2023-02-01 17:18:46'),('20221107151749','2023-02-01 17:18:57'),('20221107151752','2023-02-01 17:19:08'),('20221107151754','2023-02-01 17:19:09'),('20221107174820','2023-02-01 17:19:19'),('20221108134005','2023-02-01 17:19:20'),('20221111143627','2023-02-01 17:19:30'),('20221114160731','2023-02-01 17:19:39'),('20221124175204','2023-02-01 17:19:50'),('20221125142921','2023-02-01 17:20:00'),('20221125150407','2023-02-01 17:20:10'),('20221125155044','2023-02-01 17:20:11'),('20221128210849','2023-02-01 17:20:21'),('20221220181555','2023-02-01 17:20:21'),('20221221132905','2023-02-01 17:20:31'),('20221227171735','2023-02-01 17:20:41'),('20230109153656','2023-02-01 17:20:41'),('20230125121859','2023-02-01 17:20:42'),('20230125190817','2023-02-01 17:20:52'),('20230125202927','2023-02-01 17:21:03'),('20230125230902','2023-02-01 17:21:03'),('20230125230903','2023-02-01 17:21:05'),('20220907172428','2023-03-30 12:23:38'),('20230120194904','2023-03-30 12:23:48'),('20230120200108','2023-03-30 12:23:59'),('20230120202359','2023-03-30 12:24:09'),('20230223155412','2023-03-30 12:24:18'),('20230223155413','2023-03-30 12:24:18'),('20230227172459','2023-03-30 12:24:18'),('20230227182055','2023-03-30 12:24:28'),('20230228143348','2023-03-30 12:24:30'),('20230303192447','2023-03-30 12:24:40'),('20230314182111','2023-03-30 12:24:40'),('20230316192352','2023-03-30 12:24:40'),('20230331115549','2023-03-31 12:11:29');
/*!40000 ALTER TABLE `DoctrineMigration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Driver`
--

DROP TABLE IF EXISTS `Driver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Driver` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Driver') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Driver',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Project` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Vendor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Tested` tinyint unsigned NOT NULL DEFAULT '0',
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name_Project` (`Name`,`Project`,`Vendor`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Driver`
--

LOCK TABLES `Driver` WRITE;
/*!40000 ALTER TABLE `Driver` DISABLE KEYS */;
/*!40000 ALTER TABLE `Driver` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DriverRelease`
--

DROP TABLE IF EXISTS `DriverRelease`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DriverRelease` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DriverRelease') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DriverRelease',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Start` datetime DEFAULT NULL,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DriverRelease`
--

LOCK TABLES `DriverRelease` WRITE;
/*!40000 ALTER TABLE `DriverRelease` DISABLE KEYS */;
/*!40000 ALTER TABLE `DriverRelease` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Driver_Releases`
--

DROP TABLE IF EXISTS `Driver_Releases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Driver_Releases` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `DriverID` int NOT NULL DEFAULT '0',
  `DriverReleaseID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `DriverID` (`DriverID`),
  KEY `DriverReleaseID` (`DriverReleaseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Driver_Releases`
--

LOCK TABLES `Driver_Releases` WRITE;
/*!40000 ALTER TABLE `Driver_Releases` DISABLE KEYS */;
/*!40000 ALTER TABLE `Driver_Releases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `DupeMemberActionRequest`
--

DROP TABLE IF EXISTS `DupeMemberActionRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `DupeMemberActionRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('DupeMemberActionRequest','DupeMemberDeleteRequest','DupeMemberMergeRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DupeMemberActionRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ConfirmationHash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsConfirmed` tinyint unsigned NOT NULL DEFAULT '0',
  `ConfirmationDate` datetime DEFAULT NULL,
  `IsRevoked` tinyint unsigned NOT NULL DEFAULT '0',
  `DupeAccountID` int DEFAULT NULL,
  `PrimaryAccountID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DupeAccountID` (`DupeAccountID`),
  KEY `PrimaryAccountID` (`PrimaryAccountID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `DupeMemberActionRequest`
--

LOCK TABLES `DupeMemberActionRequest` WRITE;
/*!40000 ALTER TABLE `DupeMemberActionRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `DupeMemberActionRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Election`
--

DROP TABLE IF EXISTS `Election`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Election` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Election') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Election',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `NominationsOpen` datetime DEFAULT NULL,
  `NominationsClose` datetime DEFAULT NULL,
  `NominationAppDeadline` datetime DEFAULT NULL,
  `ElectionsOpen` datetime DEFAULT NULL,
  `ElectionsClose` datetime DEFAULT NULL,
  `TimeZoneIdentifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VoterFileID` int DEFAULT NULL,
  `CandidateApplicationFormRelationshipToOpenStackLabel` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT 'What is your relationship to OpenStack, and why is its success important to you? What would you say is your biggest contribution to OpenStack''s success to date?',
  `CandidateApplicationFormExperienceLabel` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT 'Describe your experience with other non profits or serving as a board member. How does your experience prepare you for the role of a board member?',
  `CandidateApplicationFormBoardsRoleLabel` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT 'What do you see as the Board''s role in OpenStack''s success?',
  `CandidateApplicationFormTopPriorityLabel` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT 'What do you think the top priority of the Board should be over the next year?',
  PRIMARY KEY (`ID`),
  KEY `VoterFileID` (`VoterFileID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Election`
--

LOCK TABLES `Election` WRITE;
/*!40000 ALTER TABLE `Election` DISABLE KEYS */;
/*!40000 ALTER TABLE `Election` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionPage`
--

DROP TABLE IF EXISTS `ElectionPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CandidateApplicationFormBioLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormRelationshipToOpenStackLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormExperienceLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormBoardsRoleLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormTopPriorityLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentElectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CurrentElectionID` (`CurrentElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionPage`
--

LOCK TABLES `ElectionPage` WRITE;
/*!40000 ALTER TABLE `ElectionPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionPage_Live`
--

DROP TABLE IF EXISTS `ElectionPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CandidateApplicationFormBioLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormRelationshipToOpenStackLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormExperienceLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormBoardsRoleLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormTopPriorityLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentElectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CurrentElectionID` (`CurrentElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionPage_Live`
--

LOCK TABLES `ElectionPage_Live` WRITE;
/*!40000 ALTER TABLE `ElectionPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionPage_versions`
--

DROP TABLE IF EXISTS `ElectionPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `CandidateApplicationFormBioLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormRelationshipToOpenStackLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormExperienceLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormBoardsRoleLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CandidateApplicationFormTopPriorityLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentElectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `CurrentElectionID` (`CurrentElectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionPage_versions`
--

LOCK TABLES `ElectionPage_versions` WRITE;
/*!40000 ALTER TABLE `ElectionPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionVote`
--

DROP TABLE IF EXISTS `ElectionVote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionVote` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ElectionVote') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ElectionVote',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `VoterID` int DEFAULT NULL,
  `ElectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VoterID` (`VoterID`),
  KEY `ElectionID` (`ElectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionVote`
--

LOCK TABLES `ElectionVote` WRITE;
/*!40000 ALTER TABLE `ElectionVote` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionVote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionVoterFile`
--

DROP TABLE IF EXISTS `ElectionVoterFile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionVoterFile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ElectionVoterFile') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ElectionVoterFile',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FileName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FileName` (`FileName`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionVoterFile`
--

LOCK TABLES `ElectionVoterFile` WRITE;
/*!40000 ALTER TABLE `ElectionVoterFile` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionVoterFile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionVoterPage`
--

DROP TABLE IF EXISTS `ElectionVoterPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionVoterPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MustBeMemberBy` date DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionVoterPage`
--

LOCK TABLES `ElectionVoterPage` WRITE;
/*!40000 ALTER TABLE `ElectionVoterPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionVoterPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionVoterPage_Live`
--

DROP TABLE IF EXISTS `ElectionVoterPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionVoterPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MustBeMemberBy` date DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionVoterPage_Live`
--

LOCK TABLES `ElectionVoterPage_Live` WRITE;
/*!40000 ALTER TABLE `ElectionVoterPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionVoterPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ElectionVoterPage_versions`
--

DROP TABLE IF EXISTS `ElectionVoterPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ElectionVoterPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `MustBeMemberBy` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ElectionVoterPage_versions`
--

LOCK TABLES `ElectionVoterPage_versions` WRITE;
/*!40000 ALTER TABLE `ElectionVoterPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ElectionVoterPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EmailCreationRequest`
--

DROP TABLE IF EXISTS `EmailCreationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EmailCreationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EmailCreationRequest','CalendarSyncErrorEmailRequest','MemberPromoCodeEmailCreationRequest','PresentationCreatorNotificationEmailRequest','PresentationSpeakerNotificationEmailRequest','SpeakerCreationEmailCreationRequest','SpeakerSelectionAnnouncementEmailCreationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EmailCreationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `TemplateName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Processed` tinyint unsigned NOT NULL DEFAULT '0',
  `ProcessedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EmailCreationRequest`
--

LOCK TABLES `EmailCreationRequest` WRITE;
/*!40000 ALTER TABLE `EmailCreationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `EmailCreationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntitySurvey`
--

DROP TABLE IF EXISTS `EntitySurvey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntitySurvey` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TemplateID` int DEFAULT NULL,
  `ParentID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  `EditedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TemplateID` (`TemplateID`),
  KEY `ParentID` (`ParentID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `EditedByID` (`EditedByID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntitySurvey`
--

LOCK TABLES `EntitySurvey` WRITE;
/*!40000 ALTER TABLE `EntitySurvey` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntitySurvey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntitySurveyTemplate`
--

DROP TABLE IF EXISTS `EntitySurveyTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntitySurveyTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EntityName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `UseTeamEdition` tinyint unsigned NOT NULL DEFAULT '0',
  `ParentID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ParentID_EntityName` (`ParentID`,`EntityName`),
  KEY `ParentID` (`ParentID`),
  KEY `OwnerID` (`OwnerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntitySurveyTemplate`
--

LOCK TABLES `EntitySurveyTemplate` WRITE;
/*!40000 ALTER TABLE `EntitySurveyTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntitySurveyTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EntitySurvey_EditorTeam`
--

DROP TABLE IF EXISTS `EntitySurvey_EditorTeam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EntitySurvey_EditorTeam` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EntitySurveyID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `EntitySurveyTeamMemberMailed` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `EntitySurveyID` (`EntitySurveyID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EntitySurvey_EditorTeam`
--

LOCK TABLES `EntitySurvey_EditorTeam` WRITE;
/*!40000 ALTER TABLE `EntitySurvey_EditorTeam` DISABLE KEYS */;
/*!40000 ALTER TABLE `EntitySurvey_EditorTeam` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ErrorPage`
--

DROP TABLE IF EXISTS `ErrorPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ErrorPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ErrorCode` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ErrorPage`
--

LOCK TABLES `ErrorPage` WRITE;
/*!40000 ALTER TABLE `ErrorPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `ErrorPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ErrorPage_Live`
--

DROP TABLE IF EXISTS `ErrorPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ErrorPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ErrorCode` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ErrorPage_Live`
--

LOCK TABLES `ErrorPage_Live` WRITE;
/*!40000 ALTER TABLE `ErrorPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `ErrorPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ErrorPage_versions`
--

DROP TABLE IF EXISTS `ErrorPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ErrorPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `ErrorCode` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ErrorPage_versions`
--

LOCK TABLES `ErrorPage_versions` WRITE;
/*!40000 ALTER TABLE `ErrorPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ErrorPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventAlertEmail`
--

DROP TABLE IF EXISTS `EventAlertEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventAlertEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventAlertEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventAlertEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LastEventRegistrationRequestID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LastEventRegistrationRequestID` (`LastEventRegistrationRequestID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventAlertEmail`
--

LOCK TABLES `EventAlertEmail` WRITE;
/*!40000 ALTER TABLE `EventAlertEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventAlertEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventHolder`
--

DROP TABLE IF EXISTS `EventHolder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventHolder` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HomePageBannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BannerID` int DEFAULT NULL,
  `HomePageBannerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BannerID` (`BannerID`),
  KEY `HomePageBannerID` (`HomePageBannerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventHolder`
--

LOCK TABLES `EventHolder` WRITE;
/*!40000 ALTER TABLE `EventHolder` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventHolder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventHolder_Live`
--

DROP TABLE IF EXISTS `EventHolder_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventHolder_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HomePageBannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BannerID` int DEFAULT NULL,
  `HomePageBannerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BannerID` (`BannerID`),
  KEY `HomePageBannerID` (`HomePageBannerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventHolder_Live`
--

LOCK TABLES `EventHolder_Live` WRITE;
/*!40000 ALTER TABLE `EventHolder_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventHolder_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventHolder_versions`
--

DROP TABLE IF EXISTS `EventHolder_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventHolder_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `BannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HomePageBannerLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BannerID` int DEFAULT NULL,
  `HomePageBannerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `BannerID` (`BannerID`),
  KEY `HomePageBannerID` (`HomePageBannerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventHolder_versions`
--

LOCK TABLES `EventHolder_versions` WRITE;
/*!40000 ALTER TABLE `EventHolder_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventHolder_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventPage`
--

DROP TABLE IF EXISTS `EventPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventPage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventPage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EventStartDate` date DEFAULT NULL,
  `EventEndDate` date DEFAULT NULL,
  `EventLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventLinkLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventCategory` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventLocation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventSponsor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventSponsorLogoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsSummit` tinyint unsigned NOT NULL DEFAULT '0',
  `ExternalSourceId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EventContinent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DateString` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventPage`
--

LOCK TABLES `EventPage` WRITE;
/*!40000 ALTER TABLE `EventPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventRegistrationRequest`
--

DROP TABLE IF EXISTS `EventRegistrationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventRegistrationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventRegistrationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventRegistrationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `City` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `PostDate` datetime DEFAULT NULL,
  `Sponsor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorLogoUrl` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  `isPosted` tinyint unsigned NOT NULL DEFAULT '0',
  `PointOfContactName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PointOfContactEmail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `isRejected` tinyint unsigned NOT NULL DEFAULT '0',
  `Category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `DateString` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventRegistrationRequest`
--

LOCK TABLES `EventRegistrationRequest` WRITE;
/*!40000 ALTER TABLE `EventRegistrationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventRegistrationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventSignIn`
--

DROP TABLE IF EXISTS `EventSignIn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventSignIn` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventSignIn') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventSignIn',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `EmailAddress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SigninPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SigninPageID` (`SigninPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventSignIn`
--

LOCK TABLES `EventSignIn` WRITE;
/*!40000 ALTER TABLE `EventSignIn` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventSignIn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventbriteAttendee`
--

DROP TABLE IF EXISTS `EventbriteAttendee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventbriteAttendee` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventbriteAttendee') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventbriteAttendee',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Email` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `FirstName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Price` decimal(9,2) NOT NULL DEFAULT '0.00',
  `ExternalAttendeeId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalTicketClassId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Status` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EventbriteOrderID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `EventbriteOrderID` (`EventbriteOrderID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventbriteAttendee`
--

LOCK TABLES `EventbriteAttendee` WRITE;
/*!40000 ALTER TABLE `EventbriteAttendee` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventbriteAttendee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EventbriteEvent`
--

DROP TABLE IF EXISTS `EventbriteEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `EventbriteEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('EventbriteEvent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EventbriteEvent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `EventType` enum('ORDER_PLACED','EVENT_ADDED','EVENT_UPDATE','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NONE',
  `ApiUrl` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Processed` tinyint unsigned NOT NULL DEFAULT '0',
  `ProcessedDate` datetime DEFAULT NULL,
  `FinalStatus` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalOrderId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EventbriteEvent`
--

LOCK TABLES `EventbriteEvent` WRITE;
/*!40000 ALTER TABLE `EventbriteEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `EventbriteEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ExtraQuestionAnswer`
--

DROP TABLE IF EXISTS `ExtraQuestionAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ExtraQuestionAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('ExtraQuestionAnswer','SummitOrderExtraQuestionAnswer','PresentationExtraQuestionAnswer') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ExtraQuestionAnswer',
  `Value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `QuestionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  CONSTRAINT `FK_B871C0E03F744DA2` FOREIGN KEY (`QuestionID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=381481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ExtraQuestionAnswer`
--

LOCK TABLES `ExtraQuestionAnswer` WRITE;
/*!40000 ALTER TABLE `ExtraQuestionAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `ExtraQuestionAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ExtraQuestionType`
--

DROP TABLE IF EXISTS `ExtraQuestionType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ExtraQuestionType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('ExtraQuestionType','SummitSelectionPlanExtraQuestionType','SummitOrderExtraQuestionType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitOrderExtraQuestionType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Label` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Mandatory` tinyint(1) NOT NULL DEFAULT '0',
  `Placeholder` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT '',
  `MaxSelectedValues` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ExtraQuestionType`
--

LOCK TABLES `ExtraQuestionType` WRITE;
/*!40000 ALTER TABLE `ExtraQuestionType` DISABLE KEYS */;
/*!40000 ALTER TABLE `ExtraQuestionType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ExtraQuestionTypeValue`
--

DROP TABLE IF EXISTS `ExtraQuestionTypeValue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ExtraQuestionTypeValue` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Label` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `QuestionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  CONSTRAINT `FK_DFF409E83F744DA2` FOREIGN KEY (`QuestionID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=695 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ExtraQuestionTypeValue`
--

LOCK TABLES `ExtraQuestionTypeValue` WRITE;
/*!40000 ALTER TABLE `ExtraQuestionTypeValue` DISABLE KEYS */;
/*!40000 ALTER TABLE `ExtraQuestionTypeValue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Feature`
--

DROP TABLE IF EXISTS `Feature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Feature` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Feature') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Feature',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Feature` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `URL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Benefit` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Roadmap` tinyint unsigned NOT NULL DEFAULT '0',
  `ProductPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ProductPageID` (`ProductPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Feature`
--

LOCK TABLES `Feature` WRITE;
/*!40000 ALTER TABLE `Feature` DISABLE KEYS */;
/*!40000 ALTER TABLE `Feature` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeaturedEvent`
--

DROP TABLE IF EXISTS `FeaturedEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FeaturedEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('FeaturedEvent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'FeaturedEvent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `EventID` int DEFAULT NULL,
  `PictureID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `EventID` (`EventID`),
  KEY `PictureID` (`PictureID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeaturedEvent`
--

LOCK TABLES `FeaturedEvent` WRITE;
/*!40000 ALTER TABLE `FeaturedEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeaturedEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeaturedVideo`
--

DROP TABLE IF EXISTS `FeaturedVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FeaturedVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('FeaturedVideo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'FeaturedVideo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Day` int NOT NULL DEFAULT '0',
  `YouTubeID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `URLSegment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationCategoryPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationCategoryPageID` (`PresentationCategoryPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeaturedVideo`
--

LOCK TABLES `FeaturedVideo` WRITE;
/*!40000 ALTER TABLE `FeaturedVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeaturedVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeedbackSubmission`
--

DROP TABLE IF EXISTS `FeedbackSubmission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FeedbackSubmission` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('FeedbackSubmission') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'FeedbackSubmission',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Page` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeedbackSubmission`
--

LOCK TABLES `FeedbackSubmission` WRITE;
/*!40000 ALTER TABLE `FeedbackSubmission` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeedbackSubmission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `File`
--

DROP TABLE IF EXISTS `File`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `File` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('File','Folder','CloudFolder','Image','Image_Cached','CloudImageMissing','CloudImage','AttachmentImage','BetterImage','CloudImageCached','MarketingImage','OpenStackDaysImage','CloudFile','AttachmentFile','MarketingFile') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'File',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Filename` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowInSearch` tinyint unsigned NOT NULL DEFAULT '1',
  `CloudStatus` enum('Local','Live','Error') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Local',
  `CloudSize` int NOT NULL DEFAULT '0',
  `CloudMetaJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ParentID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentID` (`ParentID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=3632 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `File`
--

LOCK TABLES `File` WRITE;
/*!40000 ALTER TABLE `File` DISABLE KEYS */;
/*!40000 ALTER TABLE `File` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FileAttachmentFieldTrack`
--

DROP TABLE IF EXISTS `FileAttachmentFieldTrack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FileAttachmentFieldTrack` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('FileAttachmentFieldTrack') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'FileAttachmentFieldTrack',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ControllerClass` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RecordID` int NOT NULL DEFAULT '0',
  `RecordClass` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `FileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `FileID` (`FileID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FileAttachmentFieldTrack`
--

LOCK TABLES `FileAttachmentFieldTrack` WRITE;
/*!40000 ALTER TABLE `FileAttachmentFieldTrack` DISABLE KEYS */;
/*!40000 ALTER TABLE `FileAttachmentFieldTrack` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Folder`
--

DROP TABLE IF EXISTS `Folder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Folder` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CloudStatus` enum('Local','Live','Error') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Local',
  `CloudSize` int NOT NULL DEFAULT '0',
  `CloudMetaJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Folder`
--

LOCK TABLES `Folder` WRITE;
/*!40000 ALTER TABLE `Folder` DISABLE KEYS */;
/*!40000 ALTER TABLE `Folder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FoundationMemberRevocationNotification`
--

DROP TABLE IF EXISTS `FoundationMemberRevocationNotification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `FoundationMemberRevocationNotification` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('FoundationMemberRevocationNotification') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'FoundationMemberRevocationNotification',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Action` enum('None','Renew','Revoked','Resign') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `ActionDate` datetime DEFAULT NULL,
  `SentDate` datetime DEFAULT NULL,
  `Hash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastElectionID` int DEFAULT NULL,
  `RecipientID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LastElectionID` (`LastElectionID`),
  KEY `RecipientID` (`RecipientID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FoundationMemberRevocationNotification`
--

LOCK TABLES `FoundationMemberRevocationNotification` WRITE;
/*!40000 ALTER TABLE `FoundationMemberRevocationNotification` DISABLE KEYS */;
/*!40000 ALTER TABLE `FoundationMemberRevocationNotification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GeoCodingQuery`
--

DROP TABLE IF EXISTS `GeoCodingQuery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GeoCodingQuery` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GeoCodingQuery') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GeoCodingQuery',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Query` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GeoCodingQuery`
--

LOCK TABLES `GeoCodingQuery` WRITE;
/*!40000 ALTER TABLE `GeoCodingQuery` DISABLE KEYS */;
/*!40000 ALTER TABLE `GeoCodingQuery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GerritChangeInfo`
--

DROP TABLE IF EXISTS `GerritChangeInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GerritChangeInfo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GerritChangeInfo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GerritChangeInfo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `kind` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FormattedChangeId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProjectName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Branch` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Topic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ChangeId` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Subject` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CreatedDate` datetime DEFAULT NULL,
  `UpdatedDate` datetime DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ChangeId` (`ChangeId`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GerritChangeInfo`
--

LOCK TABLES `GerritChangeInfo` WRITE;
/*!40000 ALTER TABLE `GerritChangeInfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `GerritChangeInfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GerritUser`
--

DROP TABLE IF EXISTS `GerritUser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GerritUser` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GerritUser') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GerritUser',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `AccountID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GerritUser`
--

LOCK TABLES `GerritUser` WRITE;
/*!40000 ALTER TABLE `GerritUser` DISABLE KEYS */;
/*!40000 ALTER TABLE `GerritUser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GitHubRepositoryConfiguration`
--

DROP TABLE IF EXISTS `GitHubRepositoryConfiguration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GitHubRepositoryConfiguration` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GitHubRepositoryConfiguration') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GitHubRepositoryConfiguration',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WebHookSecret` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RejectReasonNotMember` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RejectReasonNotFoundationMember` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RejectReasonNotCCLATeam` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GitHubRepositoryConfiguration`
--

LOCK TABLES `GitHubRepositoryConfiguration` WRITE;
/*!40000 ALTER TABLE `GitHubRepositoryConfiguration` DISABLE KEYS */;
/*!40000 ALTER TABLE `GitHubRepositoryConfiguration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GitHubRepositoryConfiguration_AllowedTeams`
--

DROP TABLE IF EXISTS `GitHubRepositoryConfiguration_AllowedTeams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GitHubRepositoryConfiguration_AllowedTeams` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GitHubRepositoryConfigurationID` int NOT NULL DEFAULT '0',
  `TeamID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GitHubRepositoryConfigurationID` (`GitHubRepositoryConfigurationID`),
  KEY `TeamID` (`TeamID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GitHubRepositoryConfiguration_AllowedTeams`
--

LOCK TABLES `GitHubRepositoryConfiguration_AllowedTeams` WRITE;
/*!40000 ALTER TABLE `GitHubRepositoryConfiguration_AllowedTeams` DISABLE KEYS */;
/*!40000 ALTER TABLE `GitHubRepositoryConfiguration_AllowedTeams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GitHubRepositoryPullRequest`
--

DROP TABLE IF EXISTS `GitHubRepositoryPullRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GitHubRepositoryPullRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GitHubRepositoryPullRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GitHubRepositoryPullRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RejectReason` enum('None','Approved','NotMember','NotFoundationMember','NotCCLATeam') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `Processed` tinyint unsigned NOT NULL DEFAULT '0',
  `ProcessedDate` datetime DEFAULT NULL,
  `GitHubRepositoryID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GitHubRepositoryID` (`GitHubRepositoryID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GitHubRepositoryPullRequest`
--

LOCK TABLES `GitHubRepositoryPullRequest` WRITE;
/*!40000 ALTER TABLE `GitHubRepositoryPullRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `GitHubRepositoryPullRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Group`
--

DROP TABLE IF EXISTS `Group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Group` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Group') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Group',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Locked` tinyint unsigned NOT NULL DEFAULT '0',
  `Sort` int NOT NULL DEFAULT '0',
  `HtmlEditorConfig` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ParentID` int DEFAULT NULL,
  `IsExternal` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ParentID` (`ParentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Group`
--

LOCK TABLES `Group` WRITE;
/*!40000 ALTER TABLE `Group` DISABLE KEYS */;
/*!40000 ALTER TABLE `Group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Group_Members`
--

DROP TABLE IF EXISTS `Group_Members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Group_Members` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GroupID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `SortIndex` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `MemberID` (`MemberID`),
  CONSTRAINT `FK_Group_Members_Group` FOREIGN KEY (`GroupID`) REFERENCES `Group` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_Group_Members_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=58382 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Group_Members`
--

LOCK TABLES `Group_Members` WRITE;
/*!40000 ALTER TABLE `Group_Members` DISABLE KEYS */;
/*!40000 ALTER TABLE `Group_Members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Group_Roles`
--

DROP TABLE IF EXISTS `Group_Roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Group_Roles` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GroupID` int NOT NULL DEFAULT '0',
  `PermissionRoleID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `PermissionRoleID` (`PermissionRoleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Group_Roles`
--

LOCK TABLES `Group_Roles` WRITE;
/*!40000 ALTER TABLE `Group_Roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `Group_Roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `GuestOSType`
--

DROP TABLE IF EXISTS `GuestOSType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `GuestOSType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('GuestOSType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GuestOSType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `GuestOSType`
--

LOCK TABLES `GuestOSType` WRITE;
/*!40000 ALTER TABLE `GuestOSType` DISABLE KEYS */;
/*!40000 ALTER TABLE `GuestOSType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HackathonsPage`
--

DROP TABLE IF EXISTS `HackathonsPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HackathonsPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HackathonsPage`
--

LOCK TABLES `HackathonsPage` WRITE;
/*!40000 ALTER TABLE `HackathonsPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `HackathonsPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HackathonsPage_Live`
--

DROP TABLE IF EXISTS `HackathonsPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HackathonsPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HackathonsPage_Live`
--

LOCK TABLES `HackathonsPage_Live` WRITE;
/*!40000 ALTER TABLE `HackathonsPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `HackathonsPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HackathonsPage_versions`
--

DROP TABLE IF EXISTS `HackathonsPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HackathonsPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HackathonsPage_versions`
--

LOCK TABLES `HackathonsPage_versions` WRITE;
/*!40000 ALTER TABLE `HackathonsPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `HackathonsPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HomePage`
--

DROP TABLE IF EXISTS `HomePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HomePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FeedData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventDate` date DEFAULT NULL,
  `VideoCurrentlyPlaying` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoButtonText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoButtonUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoHeroCredit` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoHeroCreditUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitMode` tinyint unsigned NOT NULL DEFAULT '0',
  `NextPresentationStartTime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextPresentationStartDate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LiveStreamURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PromoImageID` (`PromoImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HomePage`
--

LOCK TABLES `HomePage` WRITE;
/*!40000 ALTER TABLE `HomePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `HomePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HomePage_Live`
--

DROP TABLE IF EXISTS `HomePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HomePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FeedData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventDate` date DEFAULT NULL,
  `VideoCurrentlyPlaying` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoButtonText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoButtonUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoHeroCredit` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoHeroCreditUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitMode` tinyint unsigned NOT NULL DEFAULT '0',
  `NextPresentationStartTime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextPresentationStartDate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LiveStreamURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PromoImageID` (`PromoImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HomePage_Live`
--

LOCK TABLES `HomePage_Live` WRITE;
/*!40000 ALTER TABLE `HomePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `HomePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HomePage_versions`
--

DROP TABLE IF EXISTS `HomePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HomePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `FeedData` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventDate` date DEFAULT NULL,
  `VideoCurrentlyPlaying` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoIntroSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoButtonText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoButtonUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoDatesSize` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoHeroCredit` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoHeroCreditUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitMode` tinyint unsigned NOT NULL DEFAULT '0',
  `NextPresentationStartTime` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextPresentationStartDate` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LiveStreamURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `PromoImageID` (`PromoImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HomePage_versions`
--

LOCK TABLES `HomePage_versions` WRITE;
/*!40000 ALTER TABLE `HomePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `HomePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `HyperVisorType`
--

DROP TABLE IF EXISTS `HyperVisorType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `HyperVisorType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('HyperVisorType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'HyperVisorType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `HyperVisorType`
--

LOCK TABLES `HyperVisorType` WRITE;
/*!40000 ALTER TABLE `HyperVisorType` DISABLE KEYS */;
/*!40000 ALTER TABLE `HyperVisorType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `IndexItem`
--

DROP TABLE IF EXISTS `IndexItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `IndexItem` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('IndexItem') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'IndexItem',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SectionID` (`SectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `IndexItem`
--

LOCK TABLES `IndexItem` WRITE;
/*!40000 ALTER TABLE `IndexItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `IndexItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropCapability`
--

DROP TABLE IF EXISTS `InteropCapability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropCapability` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InteropCapability') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InteropCapability',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` enum('Required','Advisory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Required',
  `TypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropCapability`
--

LOCK TABLES `InteropCapability` WRITE;
/*!40000 ALTER TABLE `InteropCapability` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropCapability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropCapabilityType`
--

DROP TABLE IF EXISTS `InteropCapabilityType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropCapabilityType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InteropCapabilityType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InteropCapabilityType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropCapabilityType`
--

LOCK TABLES `InteropCapabilityType` WRITE;
/*!40000 ALTER TABLE `InteropCapabilityType` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropCapabilityType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropDesignatedSection`
--

DROP TABLE IF EXISTS `InteropDesignatedSection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropDesignatedSection` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InteropDesignatedSection') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InteropDesignatedSection',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Guidance` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` enum('Required','Advisory','Deprecated','Removed','Informational') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Required',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropDesignatedSection`
--

LOCK TABLES `InteropDesignatedSection` WRITE;
/*!40000 ALTER TABLE `InteropDesignatedSection` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropDesignatedSection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramType`
--

DROP TABLE IF EXISTS `InteropProgramType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InteropProgramType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InteropProgramType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShortName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `RequiredCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProductExamples` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TrademarkUse` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HasCapabilities` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramType`
--

LOCK TABLES `InteropProgramType` WRITE;
/*!40000 ALTER TABLE `InteropProgramType` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramType_Capabilities`
--

DROP TABLE IF EXISTS `InteropProgramType_Capabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramType_Capabilities` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InteropProgramTypeID` int NOT NULL DEFAULT '0',
  `InteropCapabilityID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `InteropProgramTypeID` (`InteropProgramTypeID`),
  KEY `InteropCapabilityID` (`InteropCapabilityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramType_Capabilities`
--

LOCK TABLES `InteropProgramType_Capabilities` WRITE;
/*!40000 ALTER TABLE `InteropProgramType_Capabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramType_Capabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramType_DesignatedSections`
--

DROP TABLE IF EXISTS `InteropProgramType_DesignatedSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramType_DesignatedSections` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InteropProgramTypeID` int NOT NULL DEFAULT '0',
  `InteropDesignatedSectionID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `InteropProgramTypeID` (`InteropProgramTypeID`),
  KEY `InteropDesignatedSectionID` (`InteropDesignatedSectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramType_DesignatedSections`
--

LOCK TABLES `InteropProgramType_DesignatedSections` WRITE;
/*!40000 ALTER TABLE `InteropProgramType_DesignatedSections` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramType_DesignatedSections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramVersion`
--

DROP TABLE IF EXISTS `InteropProgramVersion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramVersion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InteropProgramVersion') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InteropProgramVersion',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramVersion`
--

LOCK TABLES `InteropProgramVersion` WRITE;
/*!40000 ALTER TABLE `InteropProgramVersion` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramVersion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramVersion_Capabilities`
--

DROP TABLE IF EXISTS `InteropProgramVersion_Capabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramVersion_Capabilities` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InteropProgramVersionID` int NOT NULL DEFAULT '0',
  `InteropCapabilityID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `InteropProgramVersionID` (`InteropProgramVersionID`),
  KEY `InteropCapabilityID` (`InteropCapabilityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramVersion_Capabilities`
--

LOCK TABLES `InteropProgramVersion_Capabilities` WRITE;
/*!40000 ALTER TABLE `InteropProgramVersion_Capabilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramVersion_Capabilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InteropProgramVersion_DesignatedSections`
--

DROP TABLE IF EXISTS `InteropProgramVersion_DesignatedSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InteropProgramVersion_DesignatedSections` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InteropProgramVersionID` int NOT NULL DEFAULT '0',
  `InteropDesignatedSectionID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `InteropProgramVersionID` (`InteropProgramVersionID`),
  KEY `InteropDesignatedSectionID` (`InteropDesignatedSectionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InteropProgramVersion_DesignatedSections`
--

LOCK TABLES `InteropProgramVersion_DesignatedSections` WRITE;
/*!40000 ALTER TABLE `InteropProgramVersion_DesignatedSections` DISABLE KEYS */;
/*!40000 ALTER TABLE `InteropProgramVersion_DesignatedSections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `InvolvementType`
--

DROP TABLE IF EXISTS `InvolvementType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `InvolvementType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('InvolvementType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'InvolvementType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `InvolvementType`
--

LOCK TABLES `InvolvementType` WRITE;
/*!40000 ALTER TABLE `InvolvementType` DISABLE KEYS */;
/*!40000 ALTER TABLE `InvolvementType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JSONMember`
--

DROP TABLE IF EXISTS `JSONMember`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `JSONMember` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('JSONMember') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'JSONMember',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Surname` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IRCHandle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SecondEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ThirdEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OrgAffiliations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `untilDate` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JSONMember`
--

LOCK TABLES `JSONMember` WRITE;
/*!40000 ALTER TABLE `JSONMember` DISABLE KEYS */;
/*!40000 ALTER TABLE `JSONMember` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Job`
--

DROP TABLE IF EXISTS `Job`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Job` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Job') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Job',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PostedDate` datetime DEFAULT NULL,
  `ExpirationDate` datetime DEFAULT NULL,
  `CompanyName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MoreInfoLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsFoundationJob` tinyint unsigned NOT NULL DEFAULT '0',
  `IsActive` tinyint unsigned NOT NULL DEFAULT '0',
  `Instructions2Apply` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationType` enum('N/A','Remote','Various') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Various',
  `IsCOANeeded` tinyint unsigned NOT NULL DEFAULT '0',
  `CompanyID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  `RegistrationRequestID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `TypeID` (`TypeID`),
  KEY `RegistrationRequestID` (`RegistrationRequestID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Job`
--

LOCK TABLES `Job` WRITE;
/*!40000 ALTER TABLE `Job` DISABLE KEYS */;
/*!40000 ALTER TABLE `Job` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JobAlertEmail`
--

DROP TABLE IF EXISTS `JobAlertEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `JobAlertEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('JobAlertEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'JobAlertEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LastJobRegistrationRequestID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LastJobRegistrationRequestID` (`LastJobRegistrationRequestID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JobAlertEmail`
--

LOCK TABLES `JobAlertEmail` WRITE;
/*!40000 ALTER TABLE `JobAlertEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `JobAlertEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JobLocation`
--

DROP TABLE IF EXISTS `JobLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `JobLocation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('JobLocation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'JobLocation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `State` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `JobID` int DEFAULT NULL,
  `RequestID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `JobID` (`JobID`),
  KEY `RequestID` (`RequestID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JobLocation`
--

LOCK TABLES `JobLocation` WRITE;
/*!40000 ALTER TABLE `JobLocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `JobLocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JobRegistrationRequest`
--

DROP TABLE IF EXISTS `JobRegistrationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `JobRegistrationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('JobRegistrationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'JobRegistrationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CompanyName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Instructions2Apply` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExpirationDate` datetime DEFAULT NULL,
  `PointOfContactName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PointOfContactEmail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PostDate` datetime DEFAULT NULL,
  `isPosted` tinyint unsigned NOT NULL DEFAULT '0',
  `isRejected` tinyint unsigned NOT NULL DEFAULT '0',
  `LocationType` enum('N/A','Remote','Various') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'N/A',
  `City` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsCOANeeded` tinyint unsigned NOT NULL DEFAULT '0',
  `MemberID` int DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JobRegistrationRequest`
--

LOCK TABLES `JobRegistrationRequest` WRITE;
/*!40000 ALTER TABLE `JobRegistrationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `JobRegistrationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `JobType`
--

DROP TABLE IF EXISTS `JobType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `JobType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('JobType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'JobType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `JobType`
--

LOCK TABLES `JobType` WRITE;
/*!40000 ALTER TABLE `JobType` DISABLE KEYS */;
/*!40000 ALTER TABLE `JobType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Language`
--

DROP TABLE IF EXISTS `Language`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Language` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Language') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Language',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsoCode_639_1` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Language`
--

LOCK TABLES `Language` WRITE;
/*!40000 ALTER TABLE `Language` DISABLE KEYS */;
/*!40000 ALTER TABLE `Language` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LawPayPaymentProfile`
--

DROP TABLE IF EXISTS `LawPayPaymentProfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LawPayPaymentProfile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MerchantAccountId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_5E1D41FD11D3633A` FOREIGN KEY (`ID`) REFERENCES `PaymentGatewayProfile` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LawPayPaymentProfile`
--

LOCK TABLES `LawPayPaymentProfile` WRITE;
/*!40000 ALTER TABLE `LawPayPaymentProfile` DISABLE KEYS */;
/*!40000 ALTER TABLE `LawPayPaymentProfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LegalAgreement`
--

DROP TABLE IF EXISTS `LegalAgreement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LegalAgreement` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('LegalAgreement') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'LegalAgreement',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LegalDocumentPageID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LegalDocumentPageID` (`LegalDocumentPageID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LegalAgreement`
--

LOCK TABLES `LegalAgreement` WRITE;
/*!40000 ALTER TABLE `LegalAgreement` DISABLE KEYS */;
/*!40000 ALTER TABLE `LegalAgreement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LegalDocumentPage`
--

DROP TABLE IF EXISTS `LegalDocumentPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LegalDocumentPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LegalDocumentFileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LegalDocumentFileID` (`LegalDocumentFileID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LegalDocumentPage`
--

LOCK TABLES `LegalDocumentPage` WRITE;
/*!40000 ALTER TABLE `LegalDocumentPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `LegalDocumentPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LegalDocumentPage_Live`
--

DROP TABLE IF EXISTS `LegalDocumentPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LegalDocumentPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LegalDocumentFileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LegalDocumentFileID` (`LegalDocumentFileID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LegalDocumentPage_Live`
--

LOCK TABLES `LegalDocumentPage_Live` WRITE;
/*!40000 ALTER TABLE `LegalDocumentPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `LegalDocumentPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LegalDocumentPage_versions`
--

DROP TABLE IF EXISTS `LegalDocumentPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LegalDocumentPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `LegalDocumentFileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `LegalDocumentFileID` (`LegalDocumentFileID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LegalDocumentPage_versions`
--

LOCK TABLES `LegalDocumentPage_versions` WRITE;
/*!40000 ALTER TABLE `LegalDocumentPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `LegalDocumentPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Link`
--

DROP TABLE IF EXISTS `Link`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Link` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Link','PageLink','OpenStackComponentLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Link',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `URL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IconClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Link`
--

LOCK TABLES `Link` WRITE;
/*!40000 ALTER TABLE `Link` DISABLE KEYS */;
/*!40000 ALTER TABLE `Link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LoginAttempt`
--

DROP TABLE IF EXISTS `LoginAttempt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LoginAttempt` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('LoginAttempt') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'LoginAttempt',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EmailHashed` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Status` enum('Success','Failure') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Success',
  `IP` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LoginAttempt`
--

LOCK TABLES `LoginAttempt` WRITE;
/*!40000 ALTER TABLE `LoginAttempt` DISABLE KEYS */;
/*!40000 ALTER TABLE `LoginAttempt` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoGuidelinesPage`
--

DROP TABLE IF EXISTS `LogoGuidelinesPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoGuidelinesPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Preamble` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TrademarkURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoGuidelinesPage`
--

LOCK TABLES `LogoGuidelinesPage` WRITE;
/*!40000 ALTER TABLE `LogoGuidelinesPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoGuidelinesPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoGuidelinesPage_Live`
--

DROP TABLE IF EXISTS `LogoGuidelinesPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoGuidelinesPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Preamble` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TrademarkURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoGuidelinesPage_Live`
--

LOCK TABLES `LogoGuidelinesPage_Live` WRITE;
/*!40000 ALTER TABLE `LogoGuidelinesPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoGuidelinesPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoGuidelinesPage_versions`
--

DROP TABLE IF EXISTS `LogoGuidelinesPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoGuidelinesPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `Preamble` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TrademarkURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoGuidelinesPage_versions`
--

LOCK TABLES `LogoGuidelinesPage_versions` WRITE;
/*!40000 ALTER TABLE `LogoGuidelinesPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoGuidelinesPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoRightsPage`
--

DROP TABLE IF EXISTS `LogoRightsPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoRightsPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LogoURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AllowedMembers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EchoSignCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoRightsPage`
--

LOCK TABLES `LogoRightsPage` WRITE;
/*!40000 ALTER TABLE `LogoRightsPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoRightsPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoRightsPage_Live`
--

DROP TABLE IF EXISTS `LogoRightsPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoRightsPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LogoURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AllowedMembers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EchoSignCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoRightsPage_Live`
--

LOCK TABLES `LogoRightsPage_Live` WRITE;
/*!40000 ALTER TABLE `LogoRightsPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoRightsPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoRightsPage_versions`
--

DROP TABLE IF EXISTS `LogoRightsPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoRightsPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `LogoURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AllowedMembers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EchoSignCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoRightsPage_versions`
--

LOCK TABLES `LogoRightsPage_versions` WRITE;
/*!40000 ALTER TABLE `LogoRightsPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoRightsPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `LogoRightsSubmission`
--

DROP TABLE IF EXISTS `LogoRightsSubmission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `LogoRightsSubmission` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('LogoRightsSubmission') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'LogoRightsSubmission',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhoneNumber` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProductName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompanyName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Website` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StreetAddress` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `State` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Zip` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BehalfOfCompany` tinyint unsigned NOT NULL DEFAULT '0',
  `LogoRightsPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LogoRightsPageID` (`LogoRightsPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `LogoRightsSubmission`
--

LOCK TABLES `LogoRightsSubmission` WRITE;
/*!40000 ALTER TABLE `LogoRightsSubmission` DISABLE KEYS */;
/*!40000 ALTER TABLE `LogoRightsSubmission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceAllowedInstance`
--

DROP TABLE IF EXISTS `MarketPlaceAllowedInstance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceAllowedInstance` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceAllowedInstance') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceAllowedInstance',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MaxInstances` int NOT NULL DEFAULT '0',
  `MarketPlaceTypeID` int DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`MarketPlaceTypeID`,`CompanyID`),
  KEY `MarketPlaceTypeID` (`MarketPlaceTypeID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceAllowedInstance`
--

LOCK TABLES `MarketPlaceAllowedInstance` WRITE;
/*!40000 ALTER TABLE `MarketPlaceAllowedInstance` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceAllowedInstance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceDirectoryPage`
--

DROP TABLE IF EXISTS `MarketPlaceDirectoryPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceDirectoryPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `RatingCompanyID` int NOT NULL DEFAULT '0',
  `RatingBoxID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceDirectoryPage`
--

LOCK TABLES `MarketPlaceDirectoryPage` WRITE;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceDirectoryPage_Live`
--

DROP TABLE IF EXISTS `MarketPlaceDirectoryPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceDirectoryPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `RatingCompanyID` int NOT NULL DEFAULT '0',
  `RatingBoxID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceDirectoryPage_Live`
--

LOCK TABLES `MarketPlaceDirectoryPage_Live` WRITE;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceDirectoryPage_versions`
--

DROP TABLE IF EXISTS `MarketPlaceDirectoryPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceDirectoryPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `RatingCompanyID` int NOT NULL DEFAULT '0',
  `RatingBoxID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceDirectoryPage_versions`
--

LOCK TABLES `MarketPlaceDirectoryPage_versions` WRITE;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceDirectoryPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceHelpLink`
--

DROP TABLE IF EXISTS `MarketPlaceHelpLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceHelpLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceHelpLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceHelpLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `MarketPlacePageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MarketPlacePageID` (`MarketPlacePageID`),
  KEY `SortOrder` (`SortOrder`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceHelpLink`
--

LOCK TABLES `MarketPlaceHelpLink` WRITE;
/*!40000 ALTER TABLE `MarketPlaceHelpLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceHelpLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceReview`
--

DROP TABLE IF EXISTS `MarketPlaceReview`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceReview` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceReview') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceReview',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Comment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Rating` float NOT NULL DEFAULT '0',
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `MemberID` int DEFAULT NULL,
  `CompanyServiceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `CompanyServiceID` (`CompanyServiceID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceReview`
--

LOCK TABLES `MarketPlaceReview` WRITE;
/*!40000 ALTER TABLE `MarketPlaceReview` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceReview` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceType`
--

DROP TABLE IF EXISTS `MarketPlaceType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `AdminGroupID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `Slug` (`Slug`),
  KEY `AdminGroupID` (`AdminGroupID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceType`
--

LOCK TABLES `MarketPlaceType` WRITE;
/*!40000 ALTER TABLE `MarketPlaceType` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceVideo`
--

DROP TABLE IF EXISTS `MarketPlaceVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceVideo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceVideo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Length` int NOT NULL DEFAULT '0',
  `TypeID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TypeID` (`TypeID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceVideo`
--

LOCK TABLES `MarketPlaceVideo` WRITE;
/*!40000 ALTER TABLE `MarketPlaceVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceVideoDraft`
--

DROP TABLE IF EXISTS `MarketPlaceVideoDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceVideoDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceVideoDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceVideoDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Length` int NOT NULL DEFAULT '0',
  `TypeID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TypeID` (`TypeID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceVideoDraft`
--

LOCK TABLES `MarketPlaceVideoDraft` WRITE;
/*!40000 ALTER TABLE `MarketPlaceVideoDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceVideoDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketPlaceVideoType`
--

DROP TABLE IF EXISTS `MarketPlaceVideoType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketPlaceVideoType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketPlaceVideoType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketPlaceVideoType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MaxTotalVideoTime` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketPlaceVideoType`
--

LOCK TABLES `MarketPlaceVideoType` WRITE;
/*!40000 ALTER TABLE `MarketPlaceVideoType` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketPlaceVideoType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingCollateral`
--

DROP TABLE IF EXISTS `MarketingCollateral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingCollateral` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketingCollateral') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketingCollateral',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowGlobe` tinyint unsigned NOT NULL DEFAULT '0',
  `SortOrder` int NOT NULL DEFAULT '0',
  `ParentPageID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingCollateral`
--

LOCK TABLES `MarketingCollateral` WRITE;
/*!40000 ALTER TABLE `MarketingCollateral` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingCollateral` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingDoc`
--

DROP TABLE IF EXISTS `MarketingDoc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingDoc` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketingDoc') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketingDoc',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `GroupName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `StickersID` int DEFAULT NULL,
  `TShirtsID` int DEFAULT NULL,
  `BannersID` int DEFAULT NULL,
  `TemplatesID` int DEFAULT NULL,
  `ThumbnailID` int DEFAULT NULL,
  `DocID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `StickersID` (`StickersID`),
  KEY `TShirtsID` (`TShirtsID`),
  KEY `BannersID` (`BannersID`),
  KEY `TemplatesID` (`TemplatesID`),
  KEY `ThumbnailID` (`ThumbnailID`),
  KEY `DocID` (`DocID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingDoc`
--

LOCK TABLES `MarketingDoc` WRITE;
/*!40000 ALTER TABLE `MarketingDoc` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingDoc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingEvent`
--

DROP TABLE IF EXISTS `MarketingEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketingEvent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketingEvent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ButtonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonLabel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `SponsorEventsID` int DEFAULT NULL,
  `PromoteEventsID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SponsorEventsID` (`SponsorEventsID`),
  KEY `PromoteEventsID` (`PromoteEventsID`),
  KEY `ImageID` (`ImageID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `SortOrder` (`SortOrder`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingEvent`
--

LOCK TABLES `MarketingEvent` WRITE;
/*!40000 ALTER TABLE `MarketingEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingFile`
--

DROP TABLE IF EXISTS `MarketingFile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingFile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SortOrder` int NOT NULL DEFAULT '0',
  `Group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CollateralFilesID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CollateralFilesID` (`CollateralFilesID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingFile`
--

LOCK TABLES `MarketingFile` WRITE;
/*!40000 ALTER TABLE `MarketingFile` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingFile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingImage`
--

DROP TABLE IF EXISTS `MarketingImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingImage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SortOrder` int NOT NULL DEFAULT '0',
  `Caption` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InvolvedImagesID` int DEFAULT NULL,
  `PromoteImagesID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `InvolvedImagesID` (`InvolvedImagesID`),
  KEY `PromoteImagesID` (`PromoteImagesID`),
  KEY `ParentPageID` (`ParentPageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingImage`
--

LOCK TABLES `MarketingImage` WRITE;
/*!40000 ALTER TABLE `MarketingImage` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingImage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingLink`
--

DROP TABLE IF EXISTS `MarketingLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketingLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketingLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `CollateralID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CollateralID` (`CollateralID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingLink`
--

LOCK TABLES `MarketingLink` WRITE;
/*!40000 ALTER TABLE `MarketingLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingPage`
--

DROP TABLE IF EXISTS `MarketingPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InvolvedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SoftwareIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphicsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoteProductIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingPage`
--

LOCK TABLES `MarketingPage` WRITE;
/*!40000 ALTER TABLE `MarketingPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingPage_Live`
--

DROP TABLE IF EXISTS `MarketingPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InvolvedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SoftwareIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphicsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoteProductIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingPage_Live`
--

LOCK TABLES `MarketingPage_Live` WRITE;
/*!40000 ALTER TABLE `MarketingPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingPage_versions`
--

DROP TABLE IF EXISTS `MarketingPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `HeaderTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `InvolvedText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SoftwareIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphicsIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PromoteProductIntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingPage_versions`
--

LOCK TABLES `MarketingPage_versions` WRITE;
/*!40000 ALTER TABLE `MarketingPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingSoftware`
--

DROP TABLE IF EXISTS `MarketingSoftware`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingSoftware` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MarketingSoftware') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MarketingSoftware',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YoutubeID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ReleaseLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `ParentPageID` int DEFAULT NULL,
  `LogoID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `LogoID` (`LogoID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `SortOrder` (`SortOrder`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingSoftware`
--

LOCK TABLES `MarketingSoftware` WRITE;
/*!40000 ALTER TABLE `MarketingSoftware` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingSoftware` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketingVideo`
--

DROP TABLE IF EXISTS `MarketingVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketingVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `VideosID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VideosID` (`VideosID`),
  KEY `ParentPageID` (`ParentPageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketingVideo`
--

LOCK TABLES `MarketingVideo` WRITE;
/*!40000 ALTER TABLE `MarketingVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketingVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MarketplaceContractTemplate`
--

DROP TABLE IF EXISTS `MarketplaceContractTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MarketplaceContractTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MarketPlaceTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MarketPlaceTypeID` (`MarketPlaceTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MarketplaceContractTemplate`
--

LOCK TABLES `MarketplaceContractTemplate` WRITE;
/*!40000 ALTER TABLE `MarketplaceContractTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `MarketplaceContractTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Mascot`
--

DROP TABLE IF EXISTS `Mascot`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Mascot` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Mascot') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Mascot',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CodeName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Hide` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Mascot`
--

LOCK TABLES `Mascot` WRITE;
/*!40000 ALTER TABLE `Mascot` DISABLE KEYS */;
/*!40000 ALTER TABLE `Mascot` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Member`
--

DROP TABLE IF EXISTS `Member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Member` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Member') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Member',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TempIDHash` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TempIDExpired` datetime DEFAULT NULL,
  `Password` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RememberLoginToken` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `NumVisit` int NOT NULL DEFAULT '0',
  `LastVisited` datetime DEFAULT NULL,
  `AutoLoginHash` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `AutoLoginExpired` datetime DEFAULT NULL,
  `PasswordEncryption` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Salt` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PasswordExpiry` date DEFAULT NULL,
  `LockedOutUntil` datetime DEFAULT NULL,
  `Locale` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `FailedLoginCount` int NOT NULL DEFAULT '0',
  `DateFormat` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TimeFormat` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IdentityURL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PresentationList` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AuthenticationToken` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `AuthenticationTokenExpire` int NOT NULL DEFAULT '0',
  `SecondEmail` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ThirdEmail` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HasBeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `ShirtSize` enum('Extra Small','Small','Medium','Large','XL','XXL','WS','WM','WL','WXL','WXXL') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Extra Small',
  `StatementOfInterest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FoodPreference` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherFood` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GitHubUser` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IRCHandle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ContactEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WeChatUser` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Projects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherProject` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubscribedToNewsletter` tinyint unsigned NOT NULL DEFAULT '0',
  `JobTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Role` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LinkedInProfile` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Suburb` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Postcode` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `City` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Gender` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TypeOfDirector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `EmailVerified` tinyint unsigned NOT NULL DEFAULT '0',
  `EmailVerifiedTokenHash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EmailVerifiedDate` datetime DEFAULT NULL,
  `LegacyMember` tinyint unsigned NOT NULL DEFAULT '0',
  `ProfileLastUpdate` datetime DEFAULT NULL,
  `Type` enum('None','Ham','Spam') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `ShowDupesOnProfile` tinyint unsigned NOT NULL DEFAULT '0',
  `ResignDate` datetime DEFAULT NULL,
  `AskOpenStackUsername` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VotingListID` int DEFAULT NULL,
  `PhotoID` int DEFAULT NULL,
  `OrgID` int DEFAULT NULL,
  `ExternalUserId` int DEFAULT NULL,
  `ExternalUserIdentifier` longtext COLLATE utf8mb4_0900_as_cs,
  `MembershipType` enum('Foundation','Community','None') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `ExternalPic` varchar(512) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Company` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `ExternalUserId` (`ExternalUserId`) USING BTREE,
  UNIQUE KEY `Email` (`Email`) USING BTREE,
  KEY `VotingListID` (`VotingListID`),
  KEY `PhotoID` (`PhotoID`),
  KEY `OrgID` (`OrgID`),
  KEY `AuthenticationToken` (`AuthenticationToken`),
  KEY `SecondEmail` (`SecondEmail`),
  KEY `ThirdEmail` (`ThirdEmail`),
  KEY `FirstName` (`FirstName`),
  KEY `Surname` (`Surname`),
  KEY `FirstName_Surname` (`FirstName`,`Surname`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=88058 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Member`
--

LOCK TABLES `Member` WRITE;
/*!40000 ALTER TABLE `Member` DISABLE KEYS */;
/*!40000 ALTER TABLE `Member` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberCalendarScheduleSummitActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `MemberCalendarScheduleSummitActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberCalendarScheduleSummitActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CalendarId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarDescription` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberCalendarScheduleSummitActionSyncWorkRequest`
--

LOCK TABLES `MemberCalendarScheduleSummitActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `MemberCalendarScheduleSummitActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberCalendarScheduleSummitActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberDeleted`
--

DROP TABLE IF EXISTS `MemberDeleted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberDeleted` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MemberDeleted') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MemberDeleted',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OriginalID` int NOT NULL DEFAULT '0',
  `FromUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MembershipType` enum('Foundation','Community','None') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberDeleted`
--

LOCK TABLES `MemberDeleted` WRITE;
/*!40000 ALTER TABLE `MemberDeleted` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberDeleted` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberEmailChange`
--

DROP TABLE IF EXISTS `MemberEmailChange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberEmailChange` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MemberEmailChange') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MemberEmailChange',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `OldValue` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `NewValue` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `PerformedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `PerformedByID` (`PerformedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberEmailChange`
--

LOCK TABLES `MemberEmailChange` WRITE;
/*!40000 ALTER TABLE `MemberEmailChange` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberEmailChange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberEstimatorFeed`
--

DROP TABLE IF EXISTS `MemberEstimatorFeed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberEstimatorFeed` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MemberEstimatorFeed') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MemberEstimatorFeed',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` enum('None','Ham','Spam') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberEstimatorFeed`
--

LOCK TABLES `MemberEstimatorFeed` WRITE;
/*!40000 ALTER TABLE `MemberEstimatorFeed` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberEstimatorFeed` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberEventScheduleSummitActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `MemberEventScheduleSummitActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberEventScheduleSummitActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitEventID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberEventScheduleSummitActionSyncWorkRequest`
--

LOCK TABLES `MemberEventScheduleSummitActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `MemberEventScheduleSummitActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberEventScheduleSummitActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberPassword`
--

DROP TABLE IF EXISTS `MemberPassword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberPassword` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MemberPassword') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MemberPassword',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Password` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Salt` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PasswordEncryption` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberPassword`
--

LOCK TABLES `MemberPassword` WRITE;
/*!40000 ALTER TABLE `MemberPassword` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberPassword` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberPasswordChange`
--

DROP TABLE IF EXISTS `MemberPasswordChange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberPasswordChange` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('MemberPasswordChange') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MemberPasswordChange',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `OldValue` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `NewValue` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `PerformedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `PerformedByID` (`PerformedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberPasswordChange`
--

LOCK TABLES `MemberPasswordChange` WRITE;
/*!40000 ALTER TABLE `MemberPasswordChange` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberPasswordChange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberPromoCodeEmailCreationRequest`
--

DROP TABLE IF EXISTS `MemberPromoCodeEmailCreationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberPromoCodeEmailCreationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PromoCodeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PromoCodeID` (`PromoCodeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberPromoCodeEmailCreationRequest`
--

LOCK TABLES `MemberPromoCodeEmailCreationRequest` WRITE;
/*!40000 ALTER TABLE `MemberPromoCodeEmailCreationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberPromoCodeEmailCreationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberScheduleSummitActionSyncWorkRequest`
--

DROP TABLE IF EXISTS `MemberScheduleSummitActionSyncWorkRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberScheduleSummitActionSyncWorkRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OwnerID` int DEFAULT NULL,
  `CalendarSyncInfoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `CalendarSyncInfoID` (`CalendarSyncInfoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberScheduleSummitActionSyncWorkRequest`
--

LOCK TABLES `MemberScheduleSummitActionSyncWorkRequest` WRITE;
/*!40000 ALTER TABLE `MemberScheduleSummitActionSyncWorkRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberScheduleSummitActionSyncWorkRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberSummitRegistrationDiscountCode`
--

DROP TABLE IF EXISTS `MemberSummitRegistrationDiscountCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberSummitRegistrationDiscountCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Type` enum('VIP','ATC','MEDIA ANALYST','SPONSOR') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'VIP',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  CONSTRAINT `FK_4A51DE511D3633A` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberSummitRegistrationDiscountCode`
--

LOCK TABLES `MemberSummitRegistrationDiscountCode` WRITE;
/*!40000 ALTER TABLE `MemberSummitRegistrationDiscountCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberSummitRegistrationDiscountCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `MemberSummitRegistrationPromoCode`
--

DROP TABLE IF EXISTS `MemberSummitRegistrationPromoCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `MemberSummitRegistrationPromoCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Type` enum('VIP','ATC','MEDIA ANALYST','SPONSOR') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'VIP',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  CONSTRAINT `FK_MemberSummitRegistrationPromoCode_PromoCode` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `MemberSummitRegistrationPromoCode`
--

LOCK TABLES `MemberSummitRegistrationPromoCode` WRITE;
/*!40000 ALTER TABLE `MemberSummitRegistrationPromoCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `MemberSummitRegistrationPromoCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Member_FavoriteSummitEvents`
--

DROP TABLE IF EXISTS `Member_FavoriteSummitEvents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Member_FavoriteSummitEvents` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MemberID` int NOT NULL DEFAULT '0',
  `SummitEventID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitEventID` (`SummitEventID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Member_FavoriteSummitEvents`
--

LOCK TABLES `Member_FavoriteSummitEvents` WRITE;
/*!40000 ALTER TABLE `Member_FavoriteSummitEvents` DISABLE KEYS */;
/*!40000 ALTER TABLE `Member_FavoriteSummitEvents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Member_Schedule`
--

DROP TABLE IF EXISTS `Member_Schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Member_Schedule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MemberID` int NOT NULL DEFAULT '0',
  `SummitEventID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitEventID` (`SummitEventID`)
) ENGINE=InnoDB AUTO_INCREMENT=73918 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Member_Schedule`
--

LOCK TABLES `Member_Schedule` WRITE;
/*!40000 ALTER TABLE `Member_Schedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `Member_Schedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Migration`
--

DROP TABLE IF EXISTS `Migration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Migration` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Migration') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Migration',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Migration`
--

LOCK TABLES `Migration` WRITE;
/*!40000 ALTER TABLE `Migration` DISABLE KEYS */;
/*!40000 ALTER TABLE `Migration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NewDataModelSurveyMigrationMapping`
--

DROP TABLE IF EXISTS `NewDataModelSurveyMigrationMapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NewDataModelSurveyMigrationMapping` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OriginFieldID` int DEFAULT NULL,
  `OriginSurveyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OriginFieldID` (`OriginFieldID`),
  KEY `OriginSurveyID` (`OriginSurveyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NewDataModelSurveyMigrationMapping`
--

LOCK TABLES `NewDataModelSurveyMigrationMapping` WRITE;
/*!40000 ALTER TABLE `NewDataModelSurveyMigrationMapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `NewDataModelSurveyMigrationMapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NewSchedulePage`
--

DROP TABLE IF EXISTS `NewSchedulePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NewSchedulePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NewSchedulePage`
--

LOCK TABLES `NewSchedulePage` WRITE;
/*!40000 ALTER TABLE `NewSchedulePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `NewSchedulePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NewSchedulePage_Live`
--

DROP TABLE IF EXISTS `NewSchedulePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NewSchedulePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NewSchedulePage_Live`
--

LOCK TABLES `NewSchedulePage_Live` WRITE;
/*!40000 ALTER TABLE `NewSchedulePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `NewSchedulePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NewSchedulePage_versions`
--

DROP TABLE IF EXISTS `NewSchedulePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NewSchedulePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NewSchedulePage_versions`
--

LOCK TABLES `NewSchedulePage_versions` WRITE;
/*!40000 ALTER TABLE `NewSchedulePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `NewSchedulePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `News`
--

DROP TABLE IF EXISTS `News`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `News` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('News') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'News',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Headline` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummaryHtmlFree` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `State` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BodyHtmlFree` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DateEmbargo` datetime DEFAULT NULL,
  `DateExpire` datetime DEFAULT NULL,
  `Rank` int NOT NULL DEFAULT '0',
  `Featured` tinyint unsigned NOT NULL DEFAULT '0',
  `Slider` tinyint unsigned NOT NULL DEFAULT '0',
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `PreApproved` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowDeclaimer` tinyint unsigned NOT NULL DEFAULT '0',
  `IsLandscape` tinyint unsigned NOT NULL DEFAULT '0',
  `Archived` tinyint unsigned NOT NULL DEFAULT '0',
  `Restored` tinyint unsigned NOT NULL DEFAULT '0',
  `Deleted` tinyint unsigned NOT NULL DEFAULT '0',
  `EmailSent` tinyint unsigned NOT NULL DEFAULT '0',
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SubmitterID` int DEFAULT NULL,
  `DocumentID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SubmitterID` (`SubmitterID`),
  KEY `DocumentID` (`DocumentID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `News`
--

LOCK TABLES `News` WRITE;
/*!40000 ALTER TABLE `News` DISABLE KEYS */;
/*!40000 ALTER TABLE `News` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NewsTag`
--

DROP TABLE IF EXISTS `NewsTag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NewsTag` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('NewsTag') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NewsTag',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NewsTag`
--

LOCK TABLES `NewsTag` WRITE;
/*!40000 ALTER TABLE `NewsTag` DISABLE KEYS */;
/*!40000 ALTER TABLE `NewsTag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `News_Tags`
--

DROP TABLE IF EXISTS `News_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `News_Tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `NewsID` int NOT NULL DEFAULT '0',
  `NewsTagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `NewsID` (`NewsID`),
  KEY `NewsTagID` (`NewsTagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `News_Tags`
--

LOCK TABLES `News_Tags` WRITE;
/*!40000 ALTER TABLE `News_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `News_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `NotMyAccountAction`
--

DROP TABLE IF EXISTS `NotMyAccountAction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `NotMyAccountAction` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('NotMyAccountAction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NotMyAccountAction',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `PrimaryAccountID` int DEFAULT NULL,
  `ForeignAccountID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PrimaryAccountID` (`PrimaryAccountID`),
  KEY `ForeignAccountID` (`ForeignAccountID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `NotMyAccountAction`
--

LOCK TABLES `NotMyAccountAction` WRITE;
/*!40000 ALTER TABLE `NotMyAccountAction` DISABLE KEYS */;
/*!40000 ALTER TABLE `NotMyAccountAction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OSLogoProgramResponse`
--

DROP TABLE IF EXISTS `OSLogoProgramResponse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OSLogoProgramResponse` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OSLogoProgramResponse') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OSLogoProgramResponse',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Surname` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Phone` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Program` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentSponsor` tinyint unsigned NOT NULL DEFAULT '0',
  `CompanyDetails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Product` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Category` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Regions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `APIExposed` tinyint unsigned NOT NULL DEFAULT '0',
  `OtherCompany` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Projects` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompanyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OSLogoProgramResponse`
--

LOCK TABLES `OSLogoProgramResponse` WRITE;
/*!40000 ALTER TABLE `OSLogoProgramResponse` DISABLE KEYS */;
/*!40000 ALTER TABLE `OSLogoProgramResponse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OSUpstreamInstituteStudent`
--

DROP TABLE IF EXISTS `OSUpstreamInstituteStudent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OSUpstreamInstituteStudent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OSUpstreamInstituteStudent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OSUpstreamInstituteStudent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OSUpstreamInstituteStudent`
--

LOCK TABLES `OSUpstreamInstituteStudent` WRITE;
/*!40000 ALTER TABLE `OSUpstreamInstituteStudent` DISABLE KEYS */;
/*!40000 ALTER TABLE `OSUpstreamInstituteStudent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Office`
--

DROP TABLE IF EXISTS `Office`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Office` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Office') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Office',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Address2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ZipCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `City` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Order` int NOT NULL DEFAULT '0',
  `ConsultantID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Office`
--

LOCK TABLES `Office` WRITE;
/*!40000 ALTER TABLE `Office` DISABLE KEYS */;
/*!40000 ALTER TABLE `Office` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OfficeDraft`
--

DROP TABLE IF EXISTS `OfficeDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OfficeDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OfficeDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OfficeDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Address2` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `State` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ZipCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `City` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Lat` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Lng` decimal(9,2) NOT NULL DEFAULT '0.00',
  `Order` int NOT NULL DEFAULT '0',
  `ConsultantID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ConsultantID` (`ConsultantID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OfficeDraft`
--

LOCK TABLES `OfficeDraft` WRITE;
/*!40000 ALTER TABLE `OfficeDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `OfficeDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OldDataModelSurveyMigrationMapping`
--

DROP TABLE IF EXISTS `OldDataModelSurveyMigrationMapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OldDataModelSurveyMigrationMapping` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OriginTable` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OriginField` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OldDataModelSurveyMigrationMapping`
--

LOCK TABLES `OldDataModelSurveyMigrationMapping` WRITE;
/*!40000 ALTER TABLE `OldDataModelSurveyMigrationMapping` DISABLE KEYS */;
/*!40000 ALTER TABLE `OldDataModelSurveyMigrationMapping` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackApiVersion`
--

DROP TABLE IF EXISTS `OpenStackApiVersion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackApiVersion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackApiVersion') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackApiVersion',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Version` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Status` enum('Deprecated','Supported','Current','Beta','Alpha') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Deprecated',
  `CreatedFromTask` tinyint unsigned NOT NULL DEFAULT '0',
  `OpenStackComponentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Version_Component` (`Version`,`OpenStackComponentID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackApiVersion`
--

LOCK TABLES `OpenStackApiVersion` WRITE;
/*!40000 ALTER TABLE `OpenStackApiVersion` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackApiVersion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent`
--

DROP TABLE IF EXISTS `OpenStackComponent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CodeName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ProjectTeam` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SupportsVersioning` tinyint unsigned NOT NULL DEFAULT '0',
  `SupportsExtensions` tinyint unsigned NOT NULL DEFAULT '0',
  `IsCoreService` tinyint unsigned NOT NULL DEFAULT '0',
  `WikiUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '1',
  `YouTubeID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VideoDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoTitle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShowOnMarketplace` tinyint unsigned NOT NULL DEFAULT '1',
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Since` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LatestReleasePTLID` int DEFAULT NULL,
  `MascotID` int DEFAULT NULL,
  `CategoryID` int DEFAULT NULL,
  `DocsLinkID` int DEFAULT NULL,
  `DownloadLinkID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `NameCodeName` (`Name`,`CodeName`),
  UNIQUE KEY `Slug` (`Slug`),
  KEY `LatestReleasePTLID` (`LatestReleasePTLID`),
  KEY `MascotID` (`MascotID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `DocsLinkID` (`DocsLinkID`),
  KEY `DownloadLinkID` (`DownloadLinkID`),
  KEY `Name` (`Name`),
  KEY `CodeName` (`CodeName`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent`
--

LOCK TABLES `OpenStackComponent` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentCapabilityCategory`
--

DROP TABLE IF EXISTS `OpenStackComponentCapabilityCategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentCapabilityCategory` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentCapabilityCategory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentCapabilityCategory',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentCapabilityCategory`
--

LOCK TABLES `OpenStackComponentCapabilityCategory` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentCapabilityCategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentCapabilityCategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentCapabilityTag`
--

DROP TABLE IF EXISTS `OpenStackComponentCapabilityTag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentCapabilityTag` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentCapabilityTag') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentCapabilityTag',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  `CategoryID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentCapabilityTag`
--

LOCK TABLES `OpenStackComponentCapabilityTag` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentCapabilityTag` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentCapabilityTag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentCategory`
--

DROP TABLE IF EXISTS `OpenStackComponentCategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentCategory` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentCategory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentCategory',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  `ParentCategoryID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentCategoryID` (`ParentCategoryID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentCategory`
--

LOCK TABLES `OpenStackComponentCategory` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentCategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentCategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentLink`
--

DROP TABLE IF EXISTS `OpenStackComponentLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LinksID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LinksID` (`LinksID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentLink`
--

LOCK TABLES `OpenStackComponentLink` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentRelatedContent`
--

DROP TABLE IF EXISTS `OpenStackComponentRelatedContent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentRelatedContent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentRelatedContent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentRelatedContent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OpenStackComponentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentRelatedContent`
--

LOCK TABLES `OpenStackComponentRelatedContent` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentRelatedContent` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentRelatedContent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentReleaseCaveat`
--

DROP TABLE IF EXISTS `OpenStackComponentReleaseCaveat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentReleaseCaveat` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentReleaseCaveat') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentReleaseCaveat',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` enum('NotSet','InstallationGuide','QualityOfPackages','ProductionUse','SDKSupport') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NotSet',
  `ReleaseID` int DEFAULT NULL,
  `ComponentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReleaseID` (`ReleaseID`),
  KEY `ComponentID` (`ComponentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentReleaseCaveat`
--

LOCK TABLES `OpenStackComponentReleaseCaveat` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentReleaseCaveat` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentReleaseCaveat` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponentTag`
--

DROP TABLE IF EXISTS `OpenStackComponentTag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponentTag` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackComponentTag') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackComponentTag',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Type` enum('maturity','info') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'maturity',
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LabelTranslationKey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DescriptionTranslationKey` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponentTag`
--

LOCK TABLES `OpenStackComponentTag` WRITE;
/*!40000 ALTER TABLE `OpenStackComponentTag` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponentTag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent_CapabilityTags`
--

DROP TABLE IF EXISTS `OpenStackComponent_CapabilityTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent_CapabilityTags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `OpenStackComponentCapabilityTagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `OpenStackComponentCapabilityTagID` (`OpenStackComponentCapabilityTagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent_CapabilityTags`
--

LOCK TABLES `OpenStackComponent_CapabilityTags` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent_CapabilityTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent_CapabilityTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent_Dependencies`
--

DROP TABLE IF EXISTS `OpenStackComponent_Dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent_Dependencies` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent_Dependencies`
--

LOCK TABLES `OpenStackComponent_Dependencies` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent_Dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent_Dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent_RelatedComponents`
--

DROP TABLE IF EXISTS `OpenStackComponent_RelatedComponents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent_RelatedComponents` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent_RelatedComponents`
--

LOCK TABLES `OpenStackComponent_RelatedComponents` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent_RelatedComponents` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent_RelatedComponents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent_SupportTeams`
--

DROP TABLE IF EXISTS `OpenStackComponent_SupportTeams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent_SupportTeams` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent_SupportTeams`
--

LOCK TABLES `OpenStackComponent_SupportTeams` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent_SupportTeams` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent_SupportTeams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackComponent_Tags`
--

DROP TABLE IF EXISTS `OpenStackComponent_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackComponent_Tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `OpenStackComponentTagID` int NOT NULL DEFAULT '0',
  `SortOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `OpenStackComponentTagID` (`OpenStackComponentTagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackComponent_Tags`
--

LOCK TABLES `OpenStackComponent_Tags` WRITE;
/*!40000 ALTER TABLE `OpenStackComponent_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackComponent_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysDoc`
--

DROP TABLE IF EXISTS `OpenStackDaysDoc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysDoc` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackDaysDoc') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackDaysDoc',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SortOrder` int NOT NULL DEFAULT '0',
  `OfficialGuidelinesID` int DEFAULT NULL,
  `PlanningToolsID` int DEFAULT NULL,
  `ArtworkID` int DEFAULT NULL,
  `MediaID` int DEFAULT NULL,
  `CollateralsID` int DEFAULT NULL,
  `DocID` int DEFAULT NULL,
  `ThumbnailID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OfficialGuidelinesID` (`OfficialGuidelinesID`),
  KEY `PlanningToolsID` (`PlanningToolsID`),
  KEY `ArtworkID` (`ArtworkID`),
  KEY `MediaID` (`MediaID`),
  KEY `CollateralsID` (`CollateralsID`),
  KEY `DocID` (`DocID`),
  KEY `ThumbnailID` (`ThumbnailID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysDoc`
--

LOCK TABLES `OpenStackDaysDoc` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysDoc` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysDoc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysImage`
--

DROP TABLE IF EXISTS `OpenStackDaysImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysImage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SortOrder` int NOT NULL DEFAULT '0',
  `HeaderPicsID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HeaderPicsID` (`HeaderPicsID`),
  KEY `ParentPageID` (`ParentPageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysImage`
--

LOCK TABLES `OpenStackDaysImage` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysImage` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysImage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysPage`
--

DROP TABLE IF EXISTS `OpenStackDaysPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysPage`
--

LOCK TABLES `OpenStackDaysPage` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysPage_Live`
--

DROP TABLE IF EXISTS `OpenStackDaysPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysPage_Live`
--

LOCK TABLES `OpenStackDaysPage_Live` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysPage_versions`
--

DROP TABLE IF EXISTS `OpenStackDaysPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `AboutDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostFAQs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ToolkitDesc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ArtworkIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CollateralIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysPage_versions`
--

LOCK TABLES `OpenStackDaysPage_versions` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackDaysVideo`
--

DROP TABLE IF EXISTS `OpenStackDaysVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackDaysVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `AboutID` int DEFAULT NULL,
  `AboutHackID` int DEFAULT NULL,
  `CollateralsID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `AboutID` (`AboutID`),
  KEY `AboutHackID` (`AboutHackID`),
  KEY `CollateralsID` (`CollateralsID`),
  KEY `ParentPageID` (`ParentPageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackDaysVideo`
--

LOCK TABLES `OpenStackDaysVideo` WRITE;
/*!40000 ALTER TABLE `OpenStackDaysVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackDaysVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackFoundationStaffPage`
--

DROP TABLE IF EXISTS `OpenStackFoundationStaffPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackFoundationStaffPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ExtraFoundation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraSupporting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraFooter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackFoundationStaffPage`
--

LOCK TABLES `OpenStackFoundationStaffPage` WRITE;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackFoundationStaffPage_Live`
--

DROP TABLE IF EXISTS `OpenStackFoundationStaffPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackFoundationStaffPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ExtraFoundation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraSupporting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraFooter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackFoundationStaffPage_Live`
--

LOCK TABLES `OpenStackFoundationStaffPage_Live` WRITE;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackFoundationStaffPage_versions`
--

DROP TABLE IF EXISTS `OpenStackFoundationStaffPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackFoundationStaffPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `ExtraFoundation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraSupporting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraFooter` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackFoundationStaffPage_versions`
--

LOCK TABLES `OpenStackFoundationStaffPage_versions` WRITE;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackFoundationStaffPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementation`
--

DROP TABLE IF EXISTS `OpenStackImplementation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CompatibleWithCompute` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithStorage` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithFederatedIdentity` tinyint unsigned NOT NULL DEFAULT '0',
  `UsesIronic` tinyint unsigned NOT NULL DEFAULT '0',
  `ExpiryDate` datetime DEFAULT NULL,
  `Notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProgramVersionID` int DEFAULT NULL,
  `ReportedReleaseID` int DEFAULT NULL,
  `PassedReleaseID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ProgramVersionID` (`ProgramVersionID`),
  KEY `ReportedReleaseID` (`ReportedReleaseID`),
  KEY `PassedReleaseID` (`PassedReleaseID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementation`
--

LOCK TABLES `OpenStackImplementation` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementation` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementationApiCoverage`
--

DROP TABLE IF EXISTS `OpenStackImplementationApiCoverage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementationApiCoverage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackImplementationApiCoverage','CloudServiceOffered') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackImplementationApiCoverage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `CoveragePercent` int NOT NULL DEFAULT '0',
  `ImplementationID` int DEFAULT NULL,
  `ReleaseSupportedApiVersionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ImplementationID` (`ImplementationID`),
  KEY `ReleaseSupportedApiVersionID` (`ReleaseSupportedApiVersionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementationApiCoverage`
--

LOCK TABLES `OpenStackImplementationApiCoverage` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementationApiCoverage` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementationApiCoverage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementationApiCoverageDraft`
--

DROP TABLE IF EXISTS `OpenStackImplementationApiCoverageDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementationApiCoverageDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackImplementationApiCoverageDraft','CloudServiceOfferedDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackImplementationApiCoverageDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `CoveragePercent` int NOT NULL DEFAULT '0',
  `ImplementationID` int DEFAULT NULL,
  `ReleaseSupportedApiVersionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ImplementationID` (`ImplementationID`),
  KEY `ReleaseSupportedApiVersionID` (`ReleaseSupportedApiVersionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementationApiCoverageDraft`
--

LOCK TABLES `OpenStackImplementationApiCoverageDraft` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementationApiCoverageDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementationApiCoverageDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementationDraft`
--

DROP TABLE IF EXISTS `OpenStackImplementationDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementationDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CompatibleWithCompute` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithStorage` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithPlatform` tinyint unsigned NOT NULL DEFAULT '0',
  `ExpiryDate` datetime DEFAULT NULL,
  `CompatibleWithFederatedIdentity` tinyint unsigned NOT NULL DEFAULT '0',
  `ProgramVersionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ProgramVersionID` (`ProgramVersionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementationDraft`
--

LOCK TABLES `OpenStackImplementationDraft` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementationDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementationDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementationDraft_Guests`
--

DROP TABLE IF EXISTS `OpenStackImplementationDraft_Guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementationDraft_Guests` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackImplementationDraftID` int NOT NULL DEFAULT '0',
  `GuestOSTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationDraftID` (`OpenStackImplementationDraftID`),
  KEY `GuestOSTypeID` (`GuestOSTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementationDraft_Guests`
--

LOCK TABLES `OpenStackImplementationDraft_Guests` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementationDraft_Guests` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementationDraft_Guests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementationDraft_HyperVisors`
--

DROP TABLE IF EXISTS `OpenStackImplementationDraft_HyperVisors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementationDraft_HyperVisors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackImplementationDraftID` int NOT NULL DEFAULT '0',
  `HyperVisorTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationDraftID` (`OpenStackImplementationDraftID`),
  KEY `HyperVisorTypeID` (`HyperVisorTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementationDraft_HyperVisors`
--

LOCK TABLES `OpenStackImplementationDraft_HyperVisors` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementationDraft_HyperVisors` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementationDraft_HyperVisors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementation_Guests`
--

DROP TABLE IF EXISTS `OpenStackImplementation_Guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementation_Guests` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackImplementationID` int NOT NULL DEFAULT '0',
  `GuestOSTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationID` (`OpenStackImplementationID`),
  KEY `GuestOSTypeID` (`GuestOSTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementation_Guests`
--

LOCK TABLES `OpenStackImplementation_Guests` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementation_Guests` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementation_Guests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackImplementation_HyperVisors`
--

DROP TABLE IF EXISTS `OpenStackImplementation_HyperVisors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackImplementation_HyperVisors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackImplementationID` int NOT NULL DEFAULT '0',
  `HyperVisorTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationID` (`OpenStackImplementationID`),
  KEY `HyperVisorTypeID` (`HyperVisorTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackImplementation_HyperVisors`
--

LOCK TABLES `OpenStackImplementation_HyperVisors` WRITE;
/*!40000 ALTER TABLE `OpenStackImplementation_HyperVisors` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackImplementation_HyperVisors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackPoweredProgramHistory`
--

DROP TABLE IF EXISTS `OpenStackPoweredProgramHistory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackPoweredProgramHistory` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackPoweredProgramHistory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackPoweredProgramHistory',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `CompatibleWithComputeBefore` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithStorageBefore` tinyint unsigned NOT NULL DEFAULT '0',
  `ExpiryDateBefore` datetime DEFAULT NULL,
  `ProgramVersionIDBefore` int NOT NULL DEFAULT '0',
  `ProgramVersionNameBefore` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CompatibleWithComputeCurrent` tinyint unsigned NOT NULL DEFAULT '0',
  `CompatibleWithStorageCurrent` tinyint unsigned NOT NULL DEFAULT '0',
  `ExpiryDateCurrent` datetime DEFAULT NULL,
  `ProgramVersionIDCurrent` int NOT NULL DEFAULT '0',
  `ProgramVersionNameCurrent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ReportedReleaseIDBefore` int NOT NULL DEFAULT '0',
  `ReportedReleaseIDCurrent` int NOT NULL DEFAULT '0',
  `ReportedReleaseNameBefore` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ReportedReleaseNameCurrent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PassedReleaseIDBefore` int NOT NULL DEFAULT '0',
  `PassedReleaseIDCurrent` int NOT NULL DEFAULT '0',
  `PassedReleaseNameBefore` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PassedReleaseNameCurrent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `NotesBefore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NotesCurrent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OpenStackImplementationID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationID` (`OpenStackImplementationID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackPoweredProgramHistory`
--

LOCK TABLES `OpenStackPoweredProgramHistory` WRITE;
/*!40000 ALTER TABLE `OpenStackPoweredProgramHistory` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackPoweredProgramHistory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackRelease`
--

DROP TABLE IF EXISTS `OpenStackRelease`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackRelease` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackRelease') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackRelease',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ReleaseNumber` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ReleaseDate` date DEFAULT NULL,
  `ReleaseNotesUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` enum('Deprecated','EOL','SecuritySupported','Current','UnderDevelopment','Future') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Deprecated',
  `HasStatistics` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  UNIQUE KEY `ReleaseNumber` (`ReleaseNumber`),
  UNIQUE KEY `ReleaseDate` (`ReleaseDate`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackRelease`
--

LOCK TABLES `OpenStackRelease` WRITE;
/*!40000 ALTER TABLE `OpenStackRelease` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackRelease` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackReleaseSupportedApiVersion`
--

DROP TABLE IF EXISTS `OpenStackReleaseSupportedApiVersion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackReleaseSupportedApiVersion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackReleaseSupportedApiVersion') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackReleaseSupportedApiVersion',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ReleaseVersion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Status` enum('Deprecated','Supported','Current','Beta','Alpha') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Current',
  `CreatedFromTask` tinyint unsigned NOT NULL DEFAULT '0',
  `OpenStackComponentID` int DEFAULT NULL,
  `ApiVersionID` int DEFAULT NULL,
  `ReleaseID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Component_ApiVersion_Release` (`OpenStackComponentID`,`ApiVersionID`,`ReleaseID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`),
  KEY `ApiVersionID` (`ApiVersionID`),
  KEY `ReleaseID` (`ReleaseID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackReleaseSupportedApiVersion`
--

LOCK TABLES `OpenStackReleaseSupportedApiVersion` WRITE;
/*!40000 ALTER TABLE `OpenStackReleaseSupportedApiVersion` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackReleaseSupportedApiVersion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackRelease_OpenStackComponents`
--

DROP TABLE IF EXISTS `OpenStackRelease_OpenStackComponents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackRelease_OpenStackComponents` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackReleaseID` int NOT NULL DEFAULT '0',
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `Adoption` int NOT NULL DEFAULT '0',
  `MaturityPoints` int NOT NULL DEFAULT '0',
  `HasInstallationGuide` tinyint unsigned NOT NULL DEFAULT '0',
  `SDKSupport` int NOT NULL DEFAULT '0',
  `QualityOfPackages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MostActiveContributorsByCompanyJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MostActiveContributorsByIndividualJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ContributionsJson` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseMileStones` tinyint unsigned NOT NULL DEFAULT '0',
  `ReleaseCycleWithIntermediary` tinyint unsigned NOT NULL DEFAULT '0',
  `ReleaseIndependent` tinyint unsigned NOT NULL DEFAULT '0',
  `ReleaseTrailing` tinyint unsigned NOT NULL DEFAULT '0',
  `ReleasesNotes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CustomTeamYAMLFileName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `OpenStackReleaseID` (`OpenStackReleaseID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackRelease_OpenStackComponents`
--

LOCK TABLES `OpenStackRelease_OpenStackComponents` WRITE;
/*!40000 ALTER TABLE `OpenStackRelease_OpenStackComponents` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackRelease_OpenStackComponents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackSampleConfig`
--

DROP TABLE IF EXISTS `OpenStackSampleConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackSampleConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackSampleConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackSampleConfig',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  `CuratorID` int DEFAULT NULL,
  `ReleaseID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CuratorID` (`CuratorID`),
  KEY `ReleaseID` (`ReleaseID`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackSampleConfig`
--

LOCK TABLES `OpenStackSampleConfig` WRITE;
/*!40000 ALTER TABLE `OpenStackSampleConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackSampleConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackSampleConfigRelatedNote`
--

DROP TABLE IF EXISTS `OpenStackSampleConfigRelatedNote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackSampleConfigRelatedNote` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackSampleConfigRelatedNote') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackSampleConfigRelatedNote',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `ConfigID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ConfigID` (`ConfigID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackSampleConfigRelatedNote`
--

LOCK TABLES `OpenStackSampleConfigRelatedNote` WRITE;
/*!40000 ALTER TABLE `OpenStackSampleConfigRelatedNote` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackSampleConfigRelatedNote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackSampleConfig_OpenStackComponents`
--

DROP TABLE IF EXISTS `OpenStackSampleConfig_OpenStackComponents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackSampleConfig_OpenStackComponents` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenStackSampleConfigID` int NOT NULL DEFAULT '0',
  `OpenStackComponentID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenStackSampleConfigID` (`OpenStackSampleConfigID`),
  KEY `OpenStackComponentID` (`OpenStackComponentID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackSampleConfig_OpenStackComponents`
--

LOCK TABLES `OpenStackSampleConfig_OpenStackComponents` WRITE;
/*!40000 ALTER TABLE `OpenStackSampleConfig_OpenStackComponents` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackSampleConfig_OpenStackComponents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackSampleConfigurationType`
--

DROP TABLE IF EXISTS `OpenStackSampleConfigurationType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackSampleConfigurationType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackSampleConfigurationType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackSampleConfigurationType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  `ReleaseID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReleaseID` (`ReleaseID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackSampleConfigurationType`
--

LOCK TABLES `OpenStackSampleConfigurationType` WRITE;
/*!40000 ALTER TABLE `OpenStackSampleConfigurationType` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackSampleConfigurationType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenStackUserRequest`
--

DROP TABLE IF EXISTS `OpenStackUserRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenStackUserRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OpenStackUserRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OpenStackUserRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Company` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenStackUserRequest`
--

LOCK TABLES `OpenStackUserRequest` WRITE;
/*!40000 ALTER TABLE `OpenStackUserRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenStackUserRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenstackUser`
--

DROP TABLE IF EXISTS `OpenstackUser`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenstackUser` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ListedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuote` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuoteAuthor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `URL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Industry` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Headquarters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Category` enum('StartupSMB','Enterprise','ServiceProvider','AcademicGovResearch') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'StartupSMB',
  `UseCase` enum('Unknown','Saas','TestDev','BigDataAnalytics') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Unknown',
  `LogoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LogoID` (`LogoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenstackUser`
--

LOCK TABLES `OpenstackUser` WRITE;
/*!40000 ALTER TABLE `OpenstackUser` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenstackUser` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenstackUser_Live`
--

DROP TABLE IF EXISTS `OpenstackUser_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenstackUser_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ListedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuote` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuoteAuthor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `URL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Industry` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Headquarters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Category` enum('StartupSMB','Enterprise','ServiceProvider','AcademicGovResearch') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'StartupSMB',
  `UseCase` enum('Unknown','Saas','TestDev','BigDataAnalytics') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Unknown',
  `LogoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LogoID` (`LogoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenstackUser_Live`
--

LOCK TABLES `OpenstackUser_Live` WRITE;
/*!40000 ALTER TABLE `OpenstackUser_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenstackUser_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenstackUser_Projects`
--

DROP TABLE IF EXISTS `OpenstackUser_Projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenstackUser_Projects` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OpenstackUserID` int NOT NULL DEFAULT '0',
  `ProjectID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OpenstackUserID` (`OpenstackUserID`),
  KEY `ProjectID` (`ProjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenstackUser_Projects`
--

LOCK TABLES `OpenstackUser_Projects` WRITE;
/*!40000 ALTER TABLE `OpenstackUser_Projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenstackUser_Projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OpenstackUser_versions`
--

DROP TABLE IF EXISTS `OpenstackUser_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OpenstackUser_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `ListedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Objectives` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuote` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PullQuoteAuthor` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `URL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Industry` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Headquarters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Size` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Category` enum('StartupSMB','Enterprise','ServiceProvider','AcademicGovResearch') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'StartupSMB',
  `UseCase` enum('Unknown','Saas','TestDev','BigDataAnalytics') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Unknown',
  `LogoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `LogoID` (`LogoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OpenstackUser_versions`
--

LOCK TABLES `OpenstackUser_versions` WRITE;
/*!40000 ALTER TABLE `OpenstackUser_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `OpenstackUser_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Org`
--

DROP TABLE IF EXISTS `Org`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Org` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Org') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Org',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsStandardizedOrg` tinyint unsigned NOT NULL DEFAULT '0',
  `FoundationSupportLevel` enum('Platinum Member','Gold Member','Corporate Sponsor','Startup Sponsor','Supporting Organization') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Platinum Member',
  `OrgProfileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OrgProfileID` (`OrgProfileID`),
  KEY `ClassName` (`ClassName`),
  FULLTEXT KEY `SearchFields` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=21721 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Org`
--

LOCK TABLES `Org` WRITE;
/*!40000 ALTER TABLE `Org` DISABLE KEYS */;
/*!40000 ALTER TABLE `Org` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Org_InvolvementTypes`
--

DROP TABLE IF EXISTS `Org_InvolvementTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Org_InvolvementTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OrgID` int NOT NULL DEFAULT '0',
  `InvolvementTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `OrgID` (`OrgID`),
  KEY `InvolvementTypeID` (`InvolvementTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Org_InvolvementTypes`
--

LOCK TABLES `Org_InvolvementTypes` WRITE;
/*!40000 ALTER TABLE `Org_InvolvementTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `Org_InvolvementTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrganizationRegistrationRequest`
--

DROP TABLE IF EXISTS `OrganizationRegistrationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `OrganizationRegistrationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('OrganizationRegistrationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'OrganizationRegistrationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `OrganizationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `OrganizationID` (`OrganizationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrganizationRegistrationRequest`
--

LOCK TABLES `OrganizationRegistrationRequest` WRITE;
/*!40000 ALTER TABLE `OrganizationRegistrationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `OrganizationRegistrationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PTGDynamic`
--

DROP TABLE IF EXISTS `PTGDynamic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PTGDynamic` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhyTheChange` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelAndTravel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `WhoShouldAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhoShouldNotAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorLogos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sponsor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorSteps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupportApply` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterToAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PTGSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CodeOfConduct` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FindOutMore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FAQText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphID` int DEFAULT NULL,
  `ScheduleImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GraphID` (`GraphID`),
  KEY `ScheduleImageID` (`ScheduleImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PTGDynamic`
--

LOCK TABLES `PTGDynamic` WRITE;
/*!40000 ALTER TABLE `PTGDynamic` DISABLE KEYS */;
/*!40000 ALTER TABLE `PTGDynamic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PTGDynamic_Live`
--

DROP TABLE IF EXISTS `PTGDynamic_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PTGDynamic_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhyTheChange` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelAndTravel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `WhoShouldAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhoShouldNotAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorLogos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sponsor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorSteps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupportApply` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterToAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PTGSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CodeOfConduct` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FindOutMore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FAQText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphID` int DEFAULT NULL,
  `ScheduleImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GraphID` (`GraphID`),
  KEY `ScheduleImageID` (`ScheduleImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PTGDynamic_Live`
--

LOCK TABLES `PTGDynamic_Live` WRITE;
/*!40000 ALTER TABLE `PTGDynamic_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `PTGDynamic_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PTGDynamic_versions`
--

DROP TABLE IF EXISTS `PTGDynamic_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PTGDynamic_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhyTheChange` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelAndTravel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HotelLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `WhoShouldAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WhoShouldNotAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Benefits` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorLogos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Sponsor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorSteps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupportApply` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterToAttend` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PTGSchedule` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CodeOfConduct` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FindOutMore` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FAQText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GraphID` int DEFAULT NULL,
  `ScheduleImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `GraphID` (`GraphID`),
  KEY `ScheduleImageID` (`ScheduleImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PTGDynamic_versions`
--

LOCK TABLES `PTGDynamic_versions` WRITE;
/*!40000 ALTER TABLE `PTGDynamic_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PTGDynamic_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Page`
--

DROP TABLE IF EXISTS `Page`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Page` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IncludeJquery` tinyint unsigned NOT NULL DEFAULT '0',
  `PageJavaScript` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IncludeShadowBox` tinyint unsigned NOT NULL DEFAULT '0',
  `MetaTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PublishDate` datetime DEFAULT NULL,
  `MetaImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MetaImageID` (`MetaImageID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Page`
--

LOCK TABLES `Page` WRITE;
/*!40000 ALTER TABLE `Page` DISABLE KEYS */;
/*!40000 ALTER TABLE `Page` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageLink`
--

DROP TABLE IF EXISTS `PageLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PageID` (`PageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageLink`
--

LOCK TABLES `PageLink` WRITE;
/*!40000 ALTER TABLE `PageLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSection`
--

DROP TABLE IF EXISTS `PageSection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSection` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PageSection','PageSectionMovement','PageSectionText','PageSectionBoxes','PageSectionLinks','PageSectionPicture','PageSectionSpeakers','PageSectionSponsors','PageSectionVideos') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PageSection',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IconClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `WrapperClass` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShowInNav` tinyint unsigned NOT NULL DEFAULT '0',
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  `Order` int NOT NULL DEFAULT '0',
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSection`
--

LOCK TABLES `PageSection` WRITE;
/*!40000 ALTER TABLE `PageSection` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionBox`
--

DROP TABLE IF EXISTS `PageSectionBox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionBox` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PageSectionBox','PageSectionBoxQuote','PageSectionBoxVideo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PageSectionBox',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ButtonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonText` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Size` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  `ParentSectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentSectionID` (`ParentSectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionBox`
--

LOCK TABLES `PageSectionBox` WRITE;
/*!40000 ALTER TABLE `PageSectionBox` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionBox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionBoxQuote`
--

DROP TABLE IF EXISTS `PageSectionBoxQuote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionBoxQuote` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionBoxQuote`
--

LOCK TABLES `PageSectionBoxQuote` WRITE;
/*!40000 ALTER TABLE `PageSectionBoxQuote` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionBoxQuote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionBoxVideo`
--

DROP TABLE IF EXISTS `PageSectionBoxVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionBoxVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `YoutubeID` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ThumbnailID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ThumbnailID` (`ThumbnailID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionBoxVideo`
--

LOCK TABLES `PageSectionBoxVideo` WRITE;
/*!40000 ALTER TABLE `PageSectionBoxVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionBoxVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionLinks_Links`
--

DROP TABLE IF EXISTS `PageSectionLinks_Links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionLinks_Links` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageSectionLinksID` int NOT NULL DEFAULT '0',
  `LinkID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PageSectionLinksID` (`PageSectionLinksID`),
  KEY `LinkID` (`LinkID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionLinks_Links`
--

LOCK TABLES `PageSectionLinks_Links` WRITE;
/*!40000 ALTER TABLE `PageSectionLinks_Links` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionLinks_Links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionMovement`
--

DROP TABLE IF EXISTS `PageSectionMovement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionMovement` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TextTop` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TextBottom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PictureID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PictureID` (`PictureID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionMovement`
--

LOCK TABLES `PageSectionMovement` WRITE;
/*!40000 ALTER TABLE `PageSectionMovement` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionMovement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionPicture`
--

DROP TABLE IF EXISTS `PageSectionPicture`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionPicture` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PictureID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PictureID` (`PictureID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionPicture`
--

LOCK TABLES `PageSectionPicture` WRITE;
/*!40000 ALTER TABLE `PageSectionPicture` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionPicture` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionSpeakers_Speakers`
--

DROP TABLE IF EXISTS `PageSectionSpeakers_Speakers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionSpeakers_Speakers` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageSectionSpeakersID` int NOT NULL DEFAULT '0',
  `PresentationSpeakerID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PageSectionSpeakersID` (`PageSectionSpeakersID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionSpeakers_Speakers`
--

LOCK TABLES `PageSectionSpeakers_Speakers` WRITE;
/*!40000 ALTER TABLE `PageSectionSpeakers_Speakers` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionSpeakers_Speakers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionSponsors_Sponsors`
--

DROP TABLE IF EXISTS `PageSectionSponsors_Sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionSponsors_Sponsors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageSectionSponsorsID` int NOT NULL DEFAULT '0',
  `CompanyID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PageSectionSponsorsID` (`PageSectionSponsorsID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionSponsors_Sponsors`
--

LOCK TABLES `PageSectionSponsors_Sponsors` WRITE;
/*!40000 ALTER TABLE `PageSectionSponsors_Sponsors` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionSponsors_Sponsors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionText`
--

DROP TABLE IF EXISTS `PageSectionText`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionText` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionText`
--

LOCK TABLES `PageSectionText` WRITE;
/*!40000 ALTER TABLE `PageSectionText` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionText` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PageSectionVideos_Videos`
--

DROP TABLE IF EXISTS `PageSectionVideos_Videos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PageSectionVideos_Videos` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PageSectionVideosID` int NOT NULL DEFAULT '0',
  `VideoLinkID` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PageSectionVideosID` (`PageSectionVideosID`),
  KEY `VideoLinkID` (`VideoLinkID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PageSectionVideos_Videos`
--

LOCK TABLES `PageSectionVideos_Videos` WRITE;
/*!40000 ALTER TABLE `PageSectionVideos_Videos` DISABLE KEYS */;
/*!40000 ALTER TABLE `PageSectionVideos_Videos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Page_Live`
--

DROP TABLE IF EXISTS `Page_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Page_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IncludeJquery` tinyint unsigned NOT NULL DEFAULT '0',
  `PageJavaScript` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IncludeShadowBox` tinyint unsigned NOT NULL DEFAULT '0',
  `MetaTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PublishDate` datetime DEFAULT NULL,
  `MetaImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MetaImageID` (`MetaImageID`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Page_Live`
--

LOCK TABLES `Page_Live` WRITE;
/*!40000 ALTER TABLE `Page_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `Page_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Page_versions`
--

DROP TABLE IF EXISTS `Page_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Page_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `IncludeJquery` tinyint unsigned NOT NULL DEFAULT '0',
  `PageJavaScript` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IncludeShadowBox` tinyint unsigned NOT NULL DEFAULT '0',
  `MetaTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PublishDate` datetime DEFAULT NULL,
  `MetaImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `MetaImageID` (`MetaImageID`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Page_versions`
--

LOCK TABLES `Page_versions` WRITE;
/*!40000 ALTER TABLE `Page_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `Page_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Paper`
--

DROP TABLE IF EXISTS `Paper`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Paper` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Paper') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Paper',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Subtitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Abstract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Footer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CreatorID` int DEFAULT NULL,
  `UpdatedByID` int DEFAULT NULL,
  `BackgroundImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatorID` (`CreatorID`),
  KEY `UpdatedByID` (`UpdatedByID`),
  KEY `BackgroundImageID` (`BackgroundImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Paper`
--

LOCK TABLES `Paper` WRITE;
/*!40000 ALTER TABLE `Paper` DISABLE KEYS */;
/*!40000 ALTER TABLE `Paper` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperParagraph`
--

DROP TABLE IF EXISTS `PaperParagraph`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperParagraph` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PaperParagraph','PaperParagraphList') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PaperParagraph',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` enum('P','LIST','IMG','H5','H4') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'P',
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SectionID` (`SectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperParagraph`
--

LOCK TABLES `PaperParagraph` WRITE;
/*!40000 ALTER TABLE `PaperParagraph` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperParagraph` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperParagraphList`
--

DROP TABLE IF EXISTS `PaperParagraphList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperParagraphList` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SubType` enum('UL','OL') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'UL',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperParagraphList`
--

LOCK TABLES `PaperParagraphList` WRITE;
/*!40000 ALTER TABLE `PaperParagraphList` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperParagraphList` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperParagraphListItem`
--

DROP TABLE IF EXISTS `PaperParagraphListItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperParagraphListItem` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PaperParagraphListItem') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PaperParagraphListItem',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SubItemsContainerType` enum('UL','OL','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NONE',
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ParentID` (`ParentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperParagraphListItem`
--

LOCK TABLES `PaperParagraphListItem` WRITE;
/*!40000 ALTER TABLE `PaperParagraphListItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperParagraphListItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperSection`
--

DROP TABLE IF EXISTS `PaperSection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperSection` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PaperSection','CaseOfStudy','CaseOfStudySection','IndexSection') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PaperSection',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Subtitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `PaperID` int DEFAULT NULL,
  `ParentSectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PaperID` (`PaperID`),
  KEY `ParentSectionID` (`ParentSectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperSection`
--

LOCK TABLES `PaperSection` WRITE;
/*!40000 ALTER TABLE `PaperSection` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperSection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperTranslator`
--

DROP TABLE IF EXISTS `PaperTranslator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperTranslator` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PaperTranslator') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PaperTranslator',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `DisplayName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LanguageCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PaperID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PaperID` (`PaperID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=681 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperTranslator`
--

LOCK TABLES `PaperTranslator` WRITE;
/*!40000 ALTER TABLE `PaperTranslator` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperTranslator` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperViewerPage`
--

DROP TABLE IF EXISTS `PaperViewerPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperViewerPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PaperID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PaperID` (`PaperID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperViewerPage`
--

LOCK TABLES `PaperViewerPage` WRITE;
/*!40000 ALTER TABLE `PaperViewerPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperViewerPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperViewerPage_Live`
--

DROP TABLE IF EXISTS `PaperViewerPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperViewerPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PaperID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PaperID` (`PaperID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperViewerPage_Live`
--

LOCK TABLES `PaperViewerPage_Live` WRITE;
/*!40000 ALTER TABLE `PaperViewerPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperViewerPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaperViewerPage_versions`
--

DROP TABLE IF EXISTS `PaperViewerPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaperViewerPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `PaperID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `PaperID` (`PaperID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaperViewerPage_versions`
--

LOCK TABLES `PaperViewerPage_versions` WRITE;
/*!40000 ALTER TABLE `PaperViewerPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaperViewerPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PaymentGatewayProfile`
--

DROP TABLE IF EXISTS `PaymentGatewayProfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PaymentGatewayProfile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('PaymentGatewayProfile','StripePaymentProfile','LawPayPaymentProfile') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PaymentGatewayProfile',
  `ApplicationType` enum('Registration','Meetings') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Registration',
  `Provider` enum('Stripe','LawPay') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Stripe',
  `IsActive` tinyint(1) NOT NULL,
  `SummitID` int DEFAULT NULL,
  `IsTestModeEnabled` tinyint(1) NOT NULL DEFAULT '0',
  `LiveSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LivePublishableKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestPublishableKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_DAED06B790CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PaymentGatewayProfile`
--

LOCK TABLES `PaymentGatewayProfile` WRITE;
/*!40000 ALTER TABLE `PaymentGatewayProfile` DISABLE KEYS */;
/*!40000 ALTER TABLE `PaymentGatewayProfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PdfPage`
--

DROP TABLE IF EXISTS `PdfPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PdfPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PdfPage`
--

LOCK TABLES `PdfPage` WRITE;
/*!40000 ALTER TABLE `PdfPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `PdfPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PdfPage_Live`
--

DROP TABLE IF EXISTS `PdfPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PdfPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PdfPage_Live`
--

LOCK TABLES `PdfPage_Live` WRITE;
/*!40000 ALTER TABLE `PdfPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `PdfPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PdfPage_versions`
--

DROP TABLE IF EXISTS `PdfPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PdfPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `Sidebar` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PdfPage_versions`
--

LOCK TABLES `PdfPage_versions` WRITE;
/*!40000 ALTER TABLE `PdfPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PdfPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PermamailTemplate`
--

DROP TABLE IF EXISTS `PermamailTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PermamailTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PermamailTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PermamailTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Identifier` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `From` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestEmailAddress` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Identifier` (`Identifier`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PermamailTemplate`
--

LOCK TABLES `PermamailTemplate` WRITE;
/*!40000 ALTER TABLE `PermamailTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `PermamailTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PermamailTemplateVariable`
--

DROP TABLE IF EXISTS `PermamailTemplateVariable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PermamailTemplateVariable` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PermamailTemplateVariable') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PermamailTemplateVariable',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Variable` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ValueType` enum('static','random','query') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'static',
  `RecordClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Value` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Query` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `List` tinyint unsigned NOT NULL DEFAULT '0',
  `PermamailTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PermamailTemplateID` (`PermamailTemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PermamailTemplateVariable`
--

LOCK TABLES `PermamailTemplateVariable` WRITE;
/*!40000 ALTER TABLE `PermamailTemplateVariable` DISABLE KEYS */;
/*!40000 ALTER TABLE `PermamailTemplateVariable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Permission`
--

DROP TABLE IF EXISTS `Permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Permission` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Permission') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Permission',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Arg` int NOT NULL DEFAULT '0',
  `Type` int NOT NULL DEFAULT '1',
  `GroupID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `Code` (`Code`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Permission`
--

LOCK TABLES `Permission` WRITE;
/*!40000 ALTER TABLE `Permission` DISABLE KEYS */;
/*!40000 ALTER TABLE `Permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PermissionRole`
--

DROP TABLE IF EXISTS `PermissionRole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PermissionRole` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PermissionRole') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PermissionRole',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OnlyAdminCanApply` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Title` (`Title`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PermissionRole`
--

LOCK TABLES `PermissionRole` WRITE;
/*!40000 ALTER TABLE `PermissionRole` DISABLE KEYS */;
/*!40000 ALTER TABLE `PermissionRole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PermissionRoleCode`
--

DROP TABLE IF EXISTS `PermissionRoleCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PermissionRoleCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PermissionRoleCode') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PermissionRoleCode',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RoleID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RoleID` (`RoleID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PermissionRoleCode`
--

LOCK TABLES `PermissionRoleCode` WRITE;
/*!40000 ALTER TABLE `PermissionRoleCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `PermissionRoleCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PersonalCalendarShareInfo`
--

DROP TABLE IF EXISTS `PersonalCalendarShareInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PersonalCalendarShareInfo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PersonalCalendarShareInfo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PersonalCalendarShareInfo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Hash` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Revoked` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=45946 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PersonalCalendarShareInfo`
--

LOCK TABLES `PersonalCalendarShareInfo` WRITE;
/*!40000 ALTER TABLE `PersonalCalendarShareInfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `PersonalCalendarShareInfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Presentation`
--

DROP TABLE IF EXISTS `Presentation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Presentation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OtherTopic` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Progress` int NOT NULL DEFAULT '0',
  `Views` int NOT NULL DEFAULT '0',
  `BeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `ProblemAddressed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AttendeesExpectedLearnt` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Legacy` tinyint unsigned NOT NULL DEFAULT '0',
  `ToRecord` tinyint unsigned NOT NULL DEFAULT '0',
  `AttendingMedia` tinyint unsigned NOT NULL DEFAULT '0',
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ModeratorID` int DEFAULT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  `WillAllSpeakersAttend` tinyint(1) NOT NULL DEFAULT '0',
  `DisclaimerAcceptedDate` datetime DEFAULT NULL,
  `CustomOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ModeratorID` (`ModeratorID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  KEY `Slug` (`Slug`),
  CONSTRAINT `FK_Presentation_Moderator` FOREIGN KEY (`ModeratorID`) REFERENCES `PresentationSpeaker` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_Presentation_SummitEvent` FOREIGN KEY (`ID`) REFERENCES `SummitEvent` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_PresentationSelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=3615 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Presentation`
--

LOCK TABLES `Presentation` WRITE;
/*!40000 ALTER TABLE `Presentation` DISABLE KEYS */;
/*!40000 ALTER TABLE `Presentation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationAction`
--

DROP TABLE IF EXISTS `PresentationAction`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationAction` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('PresentationAction') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationAction',
  `IsCompleted` tinyint(1) NOT NULL DEFAULT '0',
  `TypeID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  `CreatedByID` int DEFAULT NULL,
  `UpdateByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_717B26A9280A3317A736B16E` (`PresentationID`,`TypeID`),
  KEY `TypeID` (`TypeID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `UpdateByID` (`UpdateByID`),
  CONSTRAINT `FK_717B26A9280A3317` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_717B26A9A736B16E` FOREIGN KEY (`TypeID`) REFERENCES `PresentationActionType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_717B26A9CABFF699` FOREIGN KEY (`CreatedByID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `FK_717B26A9CE220AF9` FOREIGN KEY (`UpdateByID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2480 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationAction`
--

LOCK TABLES `PresentationAction` WRITE;
/*!40000 ALTER TABLE `PresentationAction` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationAction` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationActionType`
--

DROP TABLE IF EXISTS `PresentationActionType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationActionType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('PresentationActionType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationActionType',
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_CB86755D90CF7278CF667FEC` (`SummitID`,`Label`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_CB86755D90CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationActionType`
--

LOCK TABLES `PresentationActionType` WRITE;
/*!40000 ALTER TABLE `PresentationActionType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationActionType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationActionType_SelectionPlan`
--

DROP TABLE IF EXISTS `PresentationActionType_SelectionPlan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationActionType_SelectionPlan` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CustomOrder` int NOT NULL DEFAULT '1',
  `PresentationActionTypeID` int DEFAULT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_PresentationActionTypeID_SelectionPlanID` (`PresentationActionTypeID`,`SelectionPlanID`),
  KEY `PresentationActionTypeID` (`PresentationActionTypeID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_PresentationActionType_SelectionPlan_PresentationActionType` FOREIGN KEY (`PresentationActionTypeID`) REFERENCES `PresentationActionType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresentationActionType_SelectionPlan_SelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationActionType_SelectionPlan`
--

LOCK TABLES `PresentationActionType_SelectionPlan` WRITE;
/*!40000 ALTER TABLE `PresentationActionType_SelectionPlan` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationActionType_SelectionPlan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationAttendeeVote`
--

DROP TABLE IF EXISTS `PresentationAttendeeVote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationAttendeeVote` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('PresentationAttendeeVote') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationAttendeeVote',
  `PresentationID` int DEFAULT NULL,
  `SummitAttendeeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_F3F3F0C5280A3317D008A3A9` (`PresentationID`,`SummitAttendeeID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `SummitAttendeeID` (`SummitAttendeeID`),
  CONSTRAINT `FK_F3F3F0C5280A3317` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_F3F3F0C5D008A3A9` FOREIGN KEY (`SummitAttendeeID`) REFERENCES `SummitAttendee` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationAttendeeVote`
--

LOCK TABLES `PresentationAttendeeVote` WRITE;
/*!40000 ALTER TABLE `PresentationAttendeeVote` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationAttendeeVote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategory`
--

DROP TABLE IF EXISTS `PresentationCategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategory` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationCategory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationCategory',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SessionCount` int NOT NULL DEFAULT '0',
  `AlternateCount` int NOT NULL DEFAULT '0',
  `LightningCount` int NOT NULL DEFAULT '0',
  `LightningAlternateCount` int NOT NULL DEFAULT '0',
  `VotingVisible` tinyint unsigned NOT NULL DEFAULT '0',
  `ChairVisible` tinyint unsigned NOT NULL DEFAULT '0',
  `Code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `Color` varchar(50) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IconID` int DEFAULT NULL,
  `CustomOrder` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `IconID` (`IconID`),
  CONSTRAINT `FK_CFD8AB836018720` FOREIGN KEY (`IconID`) REFERENCES `File` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategory`
--

LOCK TABLES `PresentationCategory` WRITE;
/*!40000 ALTER TABLE `PresentationCategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategoryGroup`
--

DROP TABLE IF EXISTS `PresentationCategoryGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategoryGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationCategoryGroup','PrivatePresentationCategoryGroup') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationCategoryGroup',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Color` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `MaxUniqueAttendeeVotes` int NOT NULL DEFAULT '0',
  `BeginAttendeeVotingPeriodDate` datetime DEFAULT NULL,
  `EndAttendeeVotingPeriodDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategoryGroup`
--

LOCK TABLES `PresentationCategoryGroup` WRITE;
/*!40000 ALTER TABLE `PresentationCategoryGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategoryGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategoryGroup_Categories`
--

DROP TABLE IF EXISTS `PresentationCategoryGroup_Categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategoryGroup_Categories` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationCategoryGroupID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationCategoryGroupID` (`PresentationCategoryGroupID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=191 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategoryGroup_Categories`
--

LOCK TABLES `PresentationCategoryGroup_Categories` WRITE;
/*!40000 ALTER TABLE `PresentationCategoryGroup_Categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategoryGroup_Categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategoryPage`
--

DROP TABLE IF EXISTS `PresentationCategoryPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategoryPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `StillUploading` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedVideoLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FeaturedVideoDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategoryPage`
--

LOCK TABLES `PresentationCategoryPage` WRITE;
/*!40000 ALTER TABLE `PresentationCategoryPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategoryPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategoryPage_Live`
--

DROP TABLE IF EXISTS `PresentationCategoryPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategoryPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `StillUploading` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedVideoLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FeaturedVideoDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategoryPage_Live`
--

LOCK TABLES `PresentationCategoryPage_Live` WRITE;
/*!40000 ALTER TABLE `PresentationCategoryPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategoryPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategoryPage_versions`
--

DROP TABLE IF EXISTS `PresentationCategoryPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategoryPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `StillUploading` tinyint unsigned NOT NULL DEFAULT '0',
  `FeaturedVideoLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FeaturedVideoDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategoryPage_versions`
--

LOCK TABLES `PresentationCategoryPage_versions` WRITE;
/*!40000 ALTER TABLE `PresentationCategoryPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategoryPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategory_AllowedTags`
--

DROP TABLE IF EXISTS `PresentationCategory_AllowedTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategory_AllowedTags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  `TagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_PresentationCategory_AllowedTags_PresentationCategory` FOREIGN KEY (`PresentationCategoryID`) REFERENCES `PresentationCategory` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_PresentationCategory_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=567 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategory_AllowedTags`
--

LOCK TABLES `PresentationCategory_AllowedTags` WRITE;
/*!40000 ALTER TABLE `PresentationCategory_AllowedTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategory_AllowedTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategory_ExtraQuestions`
--

DROP TABLE IF EXISTS `PresentationCategory_ExtraQuestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategory_ExtraQuestions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  `TrackQuestionTemplateID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`),
  KEY `TrackQuestionTemplateID` (`TrackQuestionTemplateID`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategory_ExtraQuestions`
--

LOCK TABLES `PresentationCategory_ExtraQuestions` WRITE;
/*!40000 ALTER TABLE `PresentationCategory_ExtraQuestions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategory_ExtraQuestions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCategory_SummitAccessLevelType`
--

DROP TABLE IF EXISTS `PresentationCategory_SummitAccessLevelType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCategory_SummitAccessLevelType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitAccessLevelTypeID` int DEFAULT NULL,
  `PresentationCategoryID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_6CFEA5C430A22149EA82A677` (`PresentationCategoryID`,`SummitAccessLevelTypeID`),
  KEY `SummitAccessLevelTypeID` (`SummitAccessLevelTypeID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCategory_SummitAccessLevelType`
--

LOCK TABLES `PresentationCategory_SummitAccessLevelType` WRITE;
/*!40000 ALTER TABLE `PresentationCategory_SummitAccessLevelType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCategory_SummitAccessLevelType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationChangeRequestPushNotification`
--

DROP TABLE IF EXISTS `PresentationChangeRequestPushNotification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationChangeRequestPushNotification` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Channel` enum('TRACKCHAIRS') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TRACKCHAIRS',
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationChangeRequestPushNotification`
--

LOCK TABLES `PresentationChangeRequestPushNotification` WRITE;
/*!40000 ALTER TABLE `PresentationChangeRequestPushNotification` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationChangeRequestPushNotification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationCreatorNotificationEmailRequest`
--

DROP TABLE IF EXISTS `PresentationCreatorNotificationEmailRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationCreatorNotificationEmailRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationCreatorNotificationEmailRequest`
--

LOCK TABLES `PresentationCreatorNotificationEmailRequest` WRITE;
/*!40000 ALTER TABLE `PresentationCreatorNotificationEmailRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationCreatorNotificationEmailRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationExtraQuestionAnswer`
--

DROP TABLE IF EXISTS `PresentationExtraQuestionAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationExtraQuestionAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`),
  CONSTRAINT `FK_FFD9217E280A3317` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `JT_PresentationExtraQuestionAnswer_ExtraQuestionAnswer` FOREIGN KEY (`ID`) REFERENCES `ExtraQuestionAnswer` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=381285 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationExtraQuestionAnswer`
--

LOCK TABLES `PresentationExtraQuestionAnswer` WRITE;
/*!40000 ALTER TABLE `PresentationExtraQuestionAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationExtraQuestionAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationLink`
--

DROP TABLE IF EXISTS `PresentationLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=8007 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationLink`
--

LOCK TABLES `PresentationLink` WRITE;
/*!40000 ALTER TABLE `PresentationLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationMaterial`
--

DROP TABLE IF EXISTS `PresentationMaterial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationMaterial` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationMaterial','PresentationLink','PresentationSlide','PresentationVideo','PresentationMediaUpload') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationMaterial',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Featured` tinyint unsigned NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '1',
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_PresentationMaterialPresentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationMaterial`
--

LOCK TABLES `PresentationMaterial` WRITE;
/*!40000 ALTER TABLE `PresentationMaterial` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationMaterial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationMediaUpload`
--

DROP TABLE IF EXISTS `PresentationMediaUpload`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationMediaUpload` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `FileName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SummitMediaUploadTypeID` int DEFAULT NULL,
  `LegacyPathFormat` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `SummitMediaUploadTypeID` (`SummitMediaUploadTypeID`),
  CONSTRAINT `FK_381AC212D70B12DA` FOREIGN KEY (`SummitMediaUploadTypeID`) REFERENCES `SummitMediaUploadType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresentationMaterial` FOREIGN KEY (`ID`) REFERENCES `PresentationMaterial` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8009 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationMediaUpload`
--

LOCK TABLES `PresentationMediaUpload` WRITE;
/*!40000 ALTER TABLE `PresentationMediaUpload` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationMediaUpload` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationPage`
--

DROP TABLE IF EXISTS `PresentationPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LegalAgreement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationDeadlineText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoLegalConsent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationSuccessText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationPage`
--

LOCK TABLES `PresentationPage` WRITE;
/*!40000 ALTER TABLE `PresentationPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationPage_Live`
--

DROP TABLE IF EXISTS `PresentationPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LegalAgreement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationDeadlineText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoLegalConsent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationSuccessText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationPage_Live`
--

LOCK TABLES `PresentationPage_Live` WRITE;
/*!40000 ALTER TABLE `PresentationPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationPage_versions`
--

DROP TABLE IF EXISTS `PresentationPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `LegalAgreement` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationDeadlineText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoLegalConsent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PresentationSuccessText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationPage_versions`
--

LOCK TABLES `PresentationPage_versions` WRITE;
/*!40000 ALTER TABLE `PresentationPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationRandomVotingList`
--

DROP TABLE IF EXISTS `PresentationRandomVotingList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationRandomVotingList` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationRandomVotingList') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationRandomVotingList',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SequenceJSON` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationRandomVotingList`
--

LOCK TABLES `PresentationRandomVotingList` WRITE;
/*!40000 ALTER TABLE `PresentationRandomVotingList` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationRandomVotingList` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSlide`
--

DROP TABLE IF EXISTS `PresentationSlide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSlide` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SlideID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SlideID` (`SlideID`)
) ENGINE=InnoDB AUTO_INCREMENT=6215 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSlide`
--

LOCK TABLES `PresentationSlide` WRITE;
/*!40000 ALTER TABLE `PresentationSlide` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSlide` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeaker`
--

DROP TABLE IF EXISTS `PresentationSpeaker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeaker` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationSpeaker') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationSpeaker',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Bio` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IRCHandle` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TwitterName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `AvailableForBureau` tinyint unsigned NOT NULL DEFAULT '0',
  `FundedTravel` tinyint unsigned NOT NULL DEFAULT '0',
  `WillingToTravel` tinyint unsigned NOT NULL DEFAULT '0',
  `Country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `WillingToPresentVideo` tinyint unsigned NOT NULL DEFAULT '0',
  `Notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CreatedFromAPI` tinyint unsigned NOT NULL DEFAULT '0',
  `OrgHasCloud` tinyint unsigned NOT NULL DEFAULT '0',
  `PhotoID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `RegistrationRequestID` int DEFAULT NULL,
  `BigPhotoID` int DEFAULT NULL,
  `Company` text COLLATE utf8mb4_0900_as_cs,
  `PhoneNumber` text COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `PhotoID` (`PhotoID`),
  KEY `MemberID` (`MemberID`),
  KEY `RegistrationRequestID` (`RegistrationRequestID`),
  KEY `FirstName` (`FirstName`),
  KEY `LastName` (`LastName`),
  KEY `FirstName_LastName` (`FirstName`,`LastName`),
  KEY `ClassName` (`ClassName`),
  KEY `BigPhotoID` (`BigPhotoID`),
  CONSTRAINT `FK_CAB885EF78E76FB9` FOREIGN KEY (`BigPhotoID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresentationSpeaker_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=29477 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeaker`
--

LOCK TABLES `PresentationSpeaker` WRITE;
/*!40000 ALTER TABLE `PresentationSpeaker` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeaker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeakerNotificationEmailRequest`
--

DROP TABLE IF EXISTS `PresentationSpeakerNotificationEmailRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeakerNotificationEmailRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SpeakerID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `PresentationID` (`PresentationID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeakerNotificationEmailRequest`
--

LOCK TABLES `PresentationSpeakerNotificationEmailRequest` WRITE;
/*!40000 ALTER TABLE `PresentationSpeakerNotificationEmailRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeakerNotificationEmailRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeakerSummitAssistanceConfirmationRequest`
--

DROP TABLE IF EXISTS `PresentationSpeakerSummitAssistanceConfirmationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeakerSummitAssistanceConfirmationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationSpeakerSummitAssistanceConfirmationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationSpeakerSummitAssistanceConfirmationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `OnSitePhoneNumber` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisteredForSummit` tinyint unsigned NOT NULL DEFAULT '0',
  `IsConfirmed` tinyint unsigned NOT NULL DEFAULT '0',
  `ConfirmationDate` datetime DEFAULT NULL,
  `ConfirmationHash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CheckedIn` tinyint unsigned NOT NULL DEFAULT '0',
  `SpeakerID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Speaker_Summit` (`SpeakerID`,`SummitID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=458 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeakerSummitAssistanceConfirmationRequest`
--

LOCK TABLES `PresentationSpeakerSummitAssistanceConfirmationRequest` WRITE;
/*!40000 ALTER TABLE `PresentationSpeakerSummitAssistanceConfirmationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeakerSummitAssistanceConfirmationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeakerUploadPresentationMaterialEmail`
--

DROP TABLE IF EXISTS `PresentationSpeakerUploadPresentationMaterialEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeakerUploadPresentationMaterialEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationSpeakerUploadPresentationMaterialEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationSpeakerUploadPresentationMaterialEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SentDate` datetime DEFAULT NULL,
  `IsRedeemed` tinyint unsigned NOT NULL DEFAULT '0',
  `RedeemedDate` datetime DEFAULT NULL,
  `Hash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Summit_Speaker_IDX` (`SummitID`,`SpeakerID`),
  KEY `SummitID` (`SummitID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeakerUploadPresentationMaterialEmail`
--

LOCK TABLES `PresentationSpeakerUploadPresentationMaterialEmail` WRITE;
/*!40000 ALTER TABLE `PresentationSpeakerUploadPresentationMaterialEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeakerUploadPresentationMaterialEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeaker_ActiveInvolvements`
--

DROP TABLE IF EXISTS `PresentationSpeaker_ActiveInvolvements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeaker_ActiveInvolvements` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationSpeakerID` int NOT NULL DEFAULT '0',
  `SpeakerActiveInvolvementID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`),
  KEY `SpeakerActiveInvolvementID` (`SpeakerActiveInvolvementID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeaker_ActiveInvolvements`
--

LOCK TABLES `PresentationSpeaker_ActiveInvolvements` WRITE;
/*!40000 ALTER TABLE `PresentationSpeaker_ActiveInvolvements` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeaker_ActiveInvolvements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeaker_Languages`
--

DROP TABLE IF EXISTS `PresentationSpeaker_Languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeaker_Languages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationSpeakerID` int NOT NULL DEFAULT '0',
  `LanguageID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`),
  KEY `LanguageID` (`LanguageID`)
) ENGINE=InnoDB AUTO_INCREMENT=3883 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeaker_Languages`
--

LOCK TABLES `PresentationSpeaker_Languages` WRITE;
/*!40000 ALTER TABLE `PresentationSpeaker_Languages` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeaker_Languages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationSpeaker_OrganizationalRoles`
--

DROP TABLE IF EXISTS `PresentationSpeaker_OrganizationalRoles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationSpeaker_OrganizationalRoles` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationSpeakerID` int NOT NULL DEFAULT '0',
  `SpeakerOrganizationalRoleID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`),
  KEY `SpeakerOrganizationalRoleID` (`SpeakerOrganizationalRoleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationSpeaker_OrganizationalRoles`
--

LOCK TABLES `PresentationSpeaker_OrganizationalRoles` WRITE;
/*!40000 ALTER TABLE `PresentationSpeaker_OrganizationalRoles` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationSpeaker_OrganizationalRoles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationTopic`
--

DROP TABLE IF EXISTS `PresentationTopic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationTopic` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationTopic') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationTopic',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationTopic`
--

LOCK TABLES `PresentationTopic` WRITE;
/*!40000 ALTER TABLE `PresentationTopic` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationTopic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationTrackChairRatingType`
--

DROP TABLE IF EXISTS `PresentationTrackChairRatingType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationTrackChairRatingType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'PresentationTrackChairRatingType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Weight` double NOT NULL DEFAULT '0',
  `CustomOrder` int NOT NULL DEFAULT '1',
  `SelectionPlanID` int NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_PresentationTrackChairRatingType_SelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationTrackChairRatingType`
--

LOCK TABLES `PresentationTrackChairRatingType` WRITE;
/*!40000 ALTER TABLE `PresentationTrackChairRatingType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationTrackChairRatingType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationTrackChairScore`
--

DROP TABLE IF EXISTS `PresentationTrackChairScore`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationTrackChairScore` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'PresentationTrackChairScore',
  `TypeID` int NOT NULL,
  `TrackChairID` int NOT NULL,
  `PresentationID` int NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_PresentationTrackChairScore_Unique` (`TypeID`,`PresentationID`,`TrackChairID`),
  KEY `TypeID` (`TypeID`),
  KEY `TrackChairID` (`TrackChairID`),
  KEY `PresentationID` (`PresentationID`),
  CONSTRAINT `FK_PresentationTrackChairScore_Presentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresentationTrackChairScore_SummitTrackChair` FOREIGN KEY (`TrackChairID`) REFERENCES `SummitTrackChair` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_PresentationTrackChairScore_Type` FOREIGN KEY (`TypeID`) REFERENCES `PresentationTrackChairScoreType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=559 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationTrackChairScore`
--

LOCK TABLES `PresentationTrackChairScore` WRITE;
/*!40000 ALTER TABLE `PresentationTrackChairScore` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationTrackChairScore` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationTrackChairScoreType`
--

DROP TABLE IF EXISTS `PresentationTrackChairScoreType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationTrackChairScoreType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'PresentationTrackChairScoreType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Score` int NOT NULL DEFAULT '1',
  `TypeID` int NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `TypeID` (`TypeID`),
  CONSTRAINT `FK_PresentationTrackChairScoreType_Type` FOREIGN KEY (`TypeID`) REFERENCES `PresentationTrackChairRatingType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationTrackChairScoreType`
--

LOCK TABLES `PresentationTrackChairScoreType` WRITE;
/*!40000 ALTER TABLE `PresentationTrackChairScoreType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationTrackChairScoreType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationTrackChairView`
--

DROP TABLE IF EXISTS `PresentationTrackChairView`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationTrackChairView` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationTrackChairView') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationTrackChairView',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `TrackChairID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TrackChairID` (`TrackChairID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_A376FB63280A3317` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_A376FB6340EBEBB0` FOREIGN KEY (`TrackChairID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6958 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationTrackChairView`
--

LOCK TABLES `PresentationTrackChairView` WRITE;
/*!40000 ALTER TABLE `PresentationTrackChairView` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationTrackChairView` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationType`
--

DROP TABLE IF EXISTS `PresentationType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MaxSpeakers` int NOT NULL DEFAULT '0',
  `MinSpeakers` int NOT NULL DEFAULT '0',
  `MaxModerators` int NOT NULL DEFAULT '0',
  `MinModerators` int NOT NULL DEFAULT '0',
  `UseSpeakers` tinyint unsigned NOT NULL DEFAULT '0',
  `AreSpeakersMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `UseModerator` tinyint unsigned NOT NULL DEFAULT '0',
  `IsModeratorMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ModeratorLabel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShouldBeAvailableOnCFP` tinyint unsigned NOT NULL DEFAULT '0',
  `AllowAttendeeVote` tinyint(1) NOT NULL DEFAULT '0',
  `AllowCustomOrdering` tinyint(1) NOT NULL DEFAULT '0',
  `AllowsSpeakerAndEventCollision` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_PresentationType_SummitEventType` FOREIGN KEY (`ID`) REFERENCES `SummitEventType` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=633 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationType`
--

LOCK TABLES `PresentationType` WRITE;
/*!40000 ALTER TABLE `PresentationType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationType_SummitMediaUploadType`
--

DROP TABLE IF EXISTS `PresentationType_SummitMediaUploadType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationType_SummitMediaUploadType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationTypeID` int DEFAULT NULL,
  `SummitMediaUploadTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_C33BDDE3962D1E63D70B12DA` (`PresentationTypeID`,`SummitMediaUploadTypeID`),
  KEY `PresentationTypeID` (`PresentationTypeID`),
  KEY `SummitMediaUploadTypeID` (`SummitMediaUploadTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=438 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationType_SummitMediaUploadType`
--

LOCK TABLES `PresentationType_SummitMediaUploadType` WRITE;
/*!40000 ALTER TABLE `PresentationType_SummitMediaUploadType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationType_SummitMediaUploadType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationVideo`
--

DROP TABLE IF EXISTS `PresentationVideo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationVideo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `YouTubeID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DateUploaded` datetime DEFAULT NULL,
  `Highlighted` tinyint unsigned NOT NULL DEFAULT '0',
  `Views` int NOT NULL DEFAULT '0',
  `ViewsLastUpdated` datetime DEFAULT NULL,
  `Processed` tinyint unsigned NOT NULL DEFAULT '0',
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalUrl` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_PresentationVideoPresentationMaterial` FOREIGN KEY (`ID`) REFERENCES `PresentationMaterial` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4187 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationVideo`
--

LOCK TABLES `PresentationVideo` WRITE;
/*!40000 ALTER TABLE `PresentationVideo` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationVideo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PresentationVote`
--

DROP TABLE IF EXISTS `PresentationVote`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PresentationVote` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PresentationVote') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PresentationVote',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Vote` int NOT NULL DEFAULT '0',
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PresentationVote`
--

LOCK TABLES `PresentationVote` WRITE;
/*!40000 ALTER TABLE `PresentationVote` DISABLE KEYS */;
/*!40000 ALTER TABLE `PresentationVote` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Presentation_Speakers`
--

DROP TABLE IF EXISTS `Presentation_Speakers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Presentation_Speakers` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationID` int NOT NULL DEFAULT '0',
  `PresentationSpeakerID` int NOT NULL DEFAULT '0',
  `IsCheckedIn` tinyint unsigned NOT NULL DEFAULT '0',
  `CustomOrder` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`),
  CONSTRAINT `FK_Presentation_Speaker_Presentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_Presentation_Speaker_Speaker` FOREIGN KEY (`PresentationSpeakerID`) REFERENCES `PresentationSpeaker` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=1988605 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Presentation_Speakers`
--

LOCK TABLES `Presentation_Speakers` WRITE;
/*!40000 ALTER TABLE `Presentation_Speakers` DISABLE KEYS */;
/*!40000 ALTER TABLE `Presentation_Speakers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Presentation_Topics`
--

DROP TABLE IF EXISTS `Presentation_Topics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Presentation_Topics` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PresentationID` int NOT NULL DEFAULT '0',
  `PresentationTopicID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `PresentationTopicID` (`PresentationTopicID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Presentation_Topics`
--

LOCK TABLES `Presentation_Topics` WRITE;
/*!40000 ALTER TABLE `Presentation_Topics` DISABLE KEYS */;
/*!40000 ALTER TABLE `Presentation_Topics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PricingSchemaType`
--

DROP TABLE IF EXISTS `PricingSchemaType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PricingSchemaType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PricingSchemaType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PricingSchemaType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PricingSchemaType`
--

LOCK TABLES `PricingSchemaType` WRITE;
/*!40000 ALTER TABLE `PricingSchemaType` DISABLE KEYS */;
/*!40000 ALTER TABLE `PricingSchemaType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PrivateCloudService`
--

DROP TABLE IF EXISTS `PrivateCloudService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PrivateCloudService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PrivateCloudService`
--

LOCK TABLES `PrivateCloudService` WRITE;
/*!40000 ALTER TABLE `PrivateCloudService` DISABLE KEYS */;
/*!40000 ALTER TABLE `PrivateCloudService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PrivatePresentationCategoryGroup`
--

DROP TABLE IF EXISTS `PrivatePresentationCategoryGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PrivatePresentationCategoryGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SubmissionBeginDate` datetime DEFAULT NULL,
  `SubmissionEndDate` datetime DEFAULT NULL,
  `MaxSubmissionAllowedPerUser` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PrivatePresentationCategoryGroup`
--

LOCK TABLES `PrivatePresentationCategoryGroup` WRITE;
/*!40000 ALTER TABLE `PrivatePresentationCategoryGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `PrivatePresentationCategoryGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PrivatePresentationCategoryGroup_AllowedGroups`
--

DROP TABLE IF EXISTS `PrivatePresentationCategoryGroup_AllowedGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PrivatePresentationCategoryGroup_AllowedGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `PrivatePresentationCategoryGroupID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `PrivatePresentationCategoryGroupID` (`PrivatePresentationCategoryGroupID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PrivatePresentationCategoryGroup_AllowedGroups`
--

LOCK TABLES `PrivatePresentationCategoryGroup_AllowedGroups` WRITE;
/*!40000 ALTER TABLE `PrivatePresentationCategoryGroup_AllowedGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `PrivatePresentationCategoryGroup_AllowedGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Project`
--

DROP TABLE IF EXISTS `Project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Project` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Project') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Project',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Codename` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Project`
--

LOCK TABLES `Project` WRITE;
/*!40000 ALTER TABLE `Project` DISABLE KEYS */;
/*!40000 ALTER TABLE `Project` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ProjectSponsorshipType`
--

DROP TABLE IF EXISTS `ProjectSponsorshipType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ProjectSponsorshipType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'ProjectSponsorshipType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `CustomOrder` int NOT NULL DEFAULT '1',
  `IsActive` tinyint(1) NOT NULL,
  `SponsoredProjectID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_2F97F881FE11D138D89789D3` (`Name`,`SponsoredProjectID`),
  UNIQUE KEY `UNIQ_2F97F88138AF345CD89789D3` (`Slug`,`SponsoredProjectID`),
  KEY `SponsoredProjectID` (`SponsoredProjectID`),
  CONSTRAINT `FK_2F97F881D89789D3` FOREIGN KEY (`SponsoredProjectID`) REFERENCES `SponsoredProject` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ProjectSponsorshipType`
--

LOCK TABLES `ProjectSponsorshipType` WRITE;
/*!40000 ALTER TABLE `ProjectSponsorshipType` DISABLE KEYS */;
/*!40000 ALTER TABLE `ProjectSponsorshipType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PublicCloudPassport`
--

DROP TABLE IF EXISTS `PublicCloudPassport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PublicCloudPassport` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PublicCloudPassport') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PublicCloudPassport',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LearnMore` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Active` tinyint unsigned NOT NULL DEFAULT '1',
  `PublicCloudID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PublicCloudID` (`PublicCloudID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PublicCloudPassport`
--

LOCK TABLES `PublicCloudPassport` WRITE;
/*!40000 ALTER TABLE `PublicCloudPassport` DISABLE KEYS */;
/*!40000 ALTER TABLE `PublicCloudPassport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PublicCloudService`
--

DROP TABLE IF EXISTS `PublicCloudService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PublicCloudService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PublicCloudService`
--

LOCK TABLES `PublicCloudService` WRITE;
/*!40000 ALTER TABLE `PublicCloudService` DISABLE KEYS */;
/*!40000 ALTER TABLE `PublicCloudService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `PushNotificationMessage`
--

DROP TABLE IF EXISTS `PushNotificationMessage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `PushNotificationMessage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('PushNotificationMessage','PresentationChangeRequestPushNotification','SummitPushNotification','ChatTeamPushNotificationMessage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'PushNotificationMessage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `IsSent` tinyint unsigned NOT NULL DEFAULT '0',
  `SentDate` datetime DEFAULT NULL,
  `Priority` enum('NORMAL','HIGH') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NORMAL',
  `Platform` enum('MOBILE','WEB') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'MOBILE',
  `OwnerID` int DEFAULT NULL,
  `ApprovedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ApprovedByID` (`ApprovedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `PushNotificationMessage`
--

LOCK TABLES `PushNotificationMessage` WRITE;
/*!40000 ALTER TABLE `PushNotificationMessage` DISABLE KEYS */;
/*!40000 ALTER TABLE `PushNotificationMessage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVP`
--

DROP TABLE IF EXISTS `RSVP`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVP` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVP') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVP',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `BeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `SeatType` enum('Regular','WaitList') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Regular',
  `SubmittedByID` int DEFAULT NULL,
  `EventID` int DEFAULT NULL,
  `EventUri` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SubmittedByID` (`SubmittedByID`),
  KEY `EventID` (`EventID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVP`
--

LOCK TABLES `RSVP` WRITE;
/*!40000 ALTER TABLE `RSVP` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVP` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPAnswer`
--

DROP TABLE IF EXISTS `RSVPAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPAnswer') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPAnswer',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `QuestionID` int DEFAULT NULL,
  `RSVPID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `RSVPID` (`RSVPID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPAnswer`
--

LOCK TABLES `RSVPAnswer` WRITE;
/*!40000 ALTER TABLE `RSVPAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPCheckBoxListQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPCheckBoxListQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPCheckBoxListQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPCheckBoxListQuestionTemplate`
--

LOCK TABLES `RSVPCheckBoxListQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPCheckBoxListQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPCheckBoxListQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPDropDownQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPDropDownQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPDropDownQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsMultiSelect` tinyint unsigned NOT NULL DEFAULT '0',
  `IsCountrySelector` tinyint unsigned NOT NULL DEFAULT '0',
  `UseChosenPlugin` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPDropDownQuestionTemplate`
--

LOCK TABLES `RSVPDropDownQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPDropDownQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPDropDownQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPEventConfiguration`
--

DROP TABLE IF EXISTS `RSVPEventConfiguration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPEventConfiguration` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPEventConfiguration') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPEventConfiguration',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MaxUserNumber` int NOT NULL DEFAULT '0',
  `MaxUserWaitListNumber` int NOT NULL DEFAULT '0',
  `SummitEventID` int DEFAULT NULL,
  `TemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `TemplateID` (`TemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPEventConfiguration`
--

LOCK TABLES `RSVPEventConfiguration` WRITE;
/*!40000 ALTER TABLE `RSVPEventConfiguration` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPEventConfiguration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPLiteralContentQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPLiteralContentQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPLiteralContentQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPLiteralContentQuestionTemplate`
--

LOCK TABLES `RSVPLiteralContentQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPLiteralContentQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPLiteralContentQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPMemberEmailQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPMemberEmailQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPMemberEmailQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPMemberEmailQuestionTemplate`
--

LOCK TABLES `RSVPMemberEmailQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPMemberEmailQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPMemberEmailQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPMemberFirstNameQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPMemberFirstNameQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPMemberFirstNameQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPMemberFirstNameQuestionTemplate`
--

LOCK TABLES `RSVPMemberFirstNameQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPMemberFirstNameQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPMemberFirstNameQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPMemberLastNameQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPMemberLastNameQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPMemberLastNameQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPMemberLastNameQuestionTemplate`
--

LOCK TABLES `RSVPMemberLastNameQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPMemberLastNameQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPMemberLastNameQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPMultiValueQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPMultiValueQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPMultiValueQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EmptyString` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DefaultValueID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DefaultValueID` (`DefaultValueID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPMultiValueQuestionTemplate`
--

LOCK TABLES `RSVPMultiValueQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPMultiValueQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPMultiValueQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPQuestionTemplate','RSVPLiteralContentQuestionTemplate','RSVPMultiValueQuestionTemplate','RSVPCheckBoxListQuestionTemplate','RSVPDropDownQuestionTemplate','RSVPRadioButtonListQuestionTemplate','RSVPSingleValueTemplateQuestion','RSVPCheckBoxQuestionTemplate','RSVPTextAreaQuestionTemplate','RSVPTextBoxQuestionTemplate','RSVPMemberEmailQuestionTemplate','RSVPMemberFirstNameQuestionTemplate','RSVPMemberLastNameQuestionTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPQuestionTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '1',
  `Mandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ReadOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `RSVPTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RSVPTemplateID` (`RSVPTemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPQuestionTemplate`
--

LOCK TABLES `RSVPQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPQuestionTemplate_DependsOn`
--

DROP TABLE IF EXISTS `RSVPQuestionTemplate_DependsOn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPQuestionTemplate_DependsOn` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RSVPQuestionTemplateID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  `ValueID` int NOT NULL DEFAULT '0',
  `Operator` enum('Equal','Not-Equal') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Equal',
  `Visibility` enum('Visible','Not-Visible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Visible',
  `BooleanOperatorOnValues` enum('And','Or') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'And',
  `DefaultValue` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RSVPQuestionTemplateID` (`RSVPQuestionTemplateID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPQuestionTemplate_DependsOn`
--

LOCK TABLES `RSVPQuestionTemplate_DependsOn` WRITE;
/*!40000 ALTER TABLE `RSVPQuestionTemplate_DependsOn` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPQuestionTemplate_DependsOn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPQuestionValueTemplate`
--

DROP TABLE IF EXISTS `RSVPQuestionValueTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPQuestionValueTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPQuestionValueTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPQuestionValueTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPQuestionValueTemplate`
--

LOCK TABLES `RSVPQuestionValueTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPQuestionValueTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPQuestionValueTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPRadioButtonListQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPRadioButtonListQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPRadioButtonListQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPRadioButtonListQuestionTemplate`
--

LOCK TABLES `RSVPRadioButtonListQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPRadioButtonListQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPRadioButtonListQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPSingleValueTemplateQuestion`
--

DROP TABLE IF EXISTS `RSVPSingleValueTemplateQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPSingleValueTemplateQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InitialValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPSingleValueTemplateQuestion`
--

LOCK TABLES `RSVPSingleValueTemplateQuestion` WRITE;
/*!40000 ALTER TABLE `RSVPSingleValueTemplateQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPSingleValueTemplateQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPSingleValueTemplateQuestion_ValidationRules`
--

DROP TABLE IF EXISTS `RSVPSingleValueTemplateQuestion_ValidationRules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPSingleValueTemplateQuestion_ValidationRules` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RSVPSingleValueTemplateQuestionID` int NOT NULL DEFAULT '0',
  `RSVPSingleValueValidationRuleID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `RSVPSingleValueTemplateQuestionID` (`RSVPSingleValueTemplateQuestionID`),
  KEY `RSVPSingleValueValidationRuleID` (`RSVPSingleValueValidationRuleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPSingleValueTemplateQuestion_ValidationRules`
--

LOCK TABLES `RSVPSingleValueTemplateQuestion_ValidationRules` WRITE;
/*!40000 ALTER TABLE `RSVPSingleValueTemplateQuestion_ValidationRules` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPSingleValueTemplateQuestion_ValidationRules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPSingleValueValidationRule`
--

DROP TABLE IF EXISTS `RSVPSingleValueValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPSingleValueValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPSingleValueValidationRule','RSVPNumberValidationRule') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPSingleValueValidationRule',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPSingleValueValidationRule`
--

LOCK TABLES `RSVPSingleValueValidationRule` WRITE;
/*!40000 ALTER TABLE `RSVPSingleValueValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPSingleValueValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPTemplate`
--

DROP TABLE IF EXISTS `RSVPTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RSVPTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RSVPTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '0',
  `CreatedByID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPTemplate`
--

LOCK TABLES `RSVPTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPTextAreaQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPTextAreaQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPTextAreaQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPTextAreaQuestionTemplate`
--

LOCK TABLES `RSVPTextAreaQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPTextAreaQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPTextAreaQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVPTextBoxQuestionTemplate`
--

DROP TABLE IF EXISTS `RSVPTextBoxQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVPTextBoxQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVPTextBoxQuestionTemplate`
--

LOCK TABLES `RSVPTextBoxQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `RSVPTextBoxQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVPTextBoxQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RSVP_Emails`
--

DROP TABLE IF EXISTS `RSVP_Emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RSVP_Emails` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RSVPID` int NOT NULL DEFAULT '0',
  `SentEmailSendGridID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `RSVPID` (`RSVPID`),
  KEY `SentEmailSendGridID` (`SentEmailSendGridID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RSVP_Emails`
--

LOCK TABLES `RSVP_Emails` WRITE;
/*!40000 ALTER TABLE `RSVP_Emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `RSVP_Emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RedeemTicketError`
--

DROP TABLE IF EXISTS `RedeemTicketError`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RedeemTicketError` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RedeemTicketError') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RedeemTicketError',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ExternalOrderId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalAttendeeId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OriginatorID` int DEFAULT NULL,
  `OriginalOwnerID` int DEFAULT NULL,
  `OriginalTicketID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OriginatorID` (`OriginatorID`),
  KEY `OriginalOwnerID` (`OriginalOwnerID`),
  KEY `OriginalTicketID` (`OriginalTicketID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RedeemTicketError`
--

LOCK TABLES `RedeemTicketError` WRITE;
/*!40000 ALTER TABLE `RedeemTicketError` DISABLE KEYS */;
/*!40000 ALTER TABLE `RedeemTicketError` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RedirectorPage`
--

DROP TABLE IF EXISTS `RedirectorPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RedirectorPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RedirectionType` enum('Internal','External') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Internal',
  `ExternalURL` varchar(2083) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LinkToID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LinkToID` (`LinkToID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RedirectorPage`
--

LOCK TABLES `RedirectorPage` WRITE;
/*!40000 ALTER TABLE `RedirectorPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `RedirectorPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RedirectorPage_Live`
--

DROP TABLE IF EXISTS `RedirectorPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RedirectorPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RedirectionType` enum('Internal','External') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Internal',
  `ExternalURL` varchar(2083) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LinkToID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LinkToID` (`LinkToID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RedirectorPage_Live`
--

LOCK TABLES `RedirectorPage_Live` WRITE;
/*!40000 ALTER TABLE `RedirectorPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `RedirectorPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RedirectorPage_versions`
--

DROP TABLE IF EXISTS `RedirectorPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RedirectorPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `RedirectionType` enum('Internal','External') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Internal',
  `ExternalURL` varchar(2083) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LinkToID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `LinkToID` (`LinkToID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RedirectorPage_versions`
--

LOCK TABLES `RedirectorPage_versions` WRITE;
/*!40000 ALTER TABLE `RedirectorPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `RedirectorPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RefStackLink`
--

DROP TABLE IF EXISTS `RefStackLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RefStackLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RefStackLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RefStackLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OpenStackImplementationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationID` (`OpenStackImplementationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RefStackLink`
--

LOCK TABLES `RefStackLink` WRITE;
/*!40000 ALTER TABLE `RefStackLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `RefStackLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Region`
--

DROP TABLE IF EXISTS `Region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Region` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Region') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Region',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Region`
--

LOCK TABLES `Region` WRITE;
/*!40000 ALTER TABLE `Region` DISABLE KEYS */;
/*!40000 ALTER TABLE `Region` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RegionalSupport`
--

DROP TABLE IF EXISTS `RegionalSupport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RegionalSupport` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RegionalSupport') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RegionalSupport',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `RegionID` int DEFAULT NULL,
  `ServiceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Region_Service` (`RegionID`,`ServiceID`),
  KEY `RegionID` (`RegionID`),
  KEY `ServiceID` (`ServiceID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RegionalSupport`
--

LOCK TABLES `RegionalSupport` WRITE;
/*!40000 ALTER TABLE `RegionalSupport` DISABLE KEYS */;
/*!40000 ALTER TABLE `RegionalSupport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RegionalSupportDraft`
--

DROP TABLE IF EXISTS `RegionalSupportDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RegionalSupportDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RegionalSupportDraft') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RegionalSupportDraft',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `RegionID` int DEFAULT NULL,
  `ServiceID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Region_Service` (`RegionID`,`ServiceID`),
  KEY `RegionID` (`RegionID`),
  KEY `ServiceID` (`ServiceID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RegionalSupportDraft`
--

LOCK TABLES `RegionalSupportDraft` WRITE;
/*!40000 ALTER TABLE `RegionalSupportDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `RegionalSupportDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RegionalSupportDraft_SupportChannelTypes`
--

DROP TABLE IF EXISTS `RegionalSupportDraft_SupportChannelTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RegionalSupportDraft_SupportChannelTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RegionalSupportDraftID` int NOT NULL DEFAULT '0',
  `SupportChannelTypeID` int NOT NULL DEFAULT '0',
  `Data` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RegionalSupportDraftID` (`RegionalSupportDraftID`),
  KEY `SupportChannelTypeID` (`SupportChannelTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RegionalSupportDraft_SupportChannelTypes`
--

LOCK TABLES `RegionalSupportDraft_SupportChannelTypes` WRITE;
/*!40000 ALTER TABLE `RegionalSupportDraft_SupportChannelTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `RegionalSupportDraft_SupportChannelTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RegionalSupport_SupportChannelTypes`
--

DROP TABLE IF EXISTS `RegionalSupport_SupportChannelTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RegionalSupport_SupportChannelTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RegionalSupportID` int NOT NULL DEFAULT '0',
  `SupportChannelTypeID` int NOT NULL DEFAULT '0',
  `Data` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RegionalSupportID` (`RegionalSupportID`),
  KEY `SupportChannelTypeID` (`SupportChannelTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RegionalSupport_SupportChannelTypes`
--

LOCK TABLES `RegionalSupport_SupportChannelTypes` WRITE;
/*!40000 ALTER TABLE `RegionalSupport_SupportChannelTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `RegionalSupport_SupportChannelTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RegionalSupportedCompanyService`
--

DROP TABLE IF EXISTS `RegionalSupportedCompanyService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RegionalSupportedCompanyService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RegionalSupportedCompanyService`
--

LOCK TABLES `RegionalSupportedCompanyService` WRITE;
/*!40000 ALTER TABLE `RegionalSupportedCompanyService` DISABLE KEYS */;
/*!40000 ALTER TABLE `RegionalSupportedCompanyService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ReleaseCycleContributor`
--

DROP TABLE IF EXISTS `ReleaseCycleContributor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ReleaseCycleContributor` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ReleaseCycleContributor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ReleaseCycleContributor',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastCommit` datetime DEFAULT NULL,
  `FirstCommit` datetime DEFAULT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IRCHandle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CommitCount` int NOT NULL DEFAULT '0',
  `ExtraEmails` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MemberID` int DEFAULT NULL,
  `ReleaseID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ReleaseID` (`ReleaseID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ReleaseCycleContributor`
--

LOCK TABLES `ReleaseCycleContributor` WRITE;
/*!40000 ALTER TABLE `ReleaseCycleContributor` DISABLE KEYS */;
/*!40000 ALTER TABLE `ReleaseCycleContributor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RemoteCloudService`
--

DROP TABLE IF EXISTS `RemoteCloudService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RemoteCloudService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HardwareSpecifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VendorManagedUpgrades` tinyint unsigned NOT NULL DEFAULT '0',
  `PricingModels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PublishedSLAs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RemoteCloudService`
--

LOCK TABLES `RemoteCloudService` WRITE;
/*!40000 ALTER TABLE `RemoteCloudService` DISABLE KEYS */;
/*!40000 ALTER TABLE `RemoteCloudService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RemoteCloudServiceDraft`
--

DROP TABLE IF EXISTS `RemoteCloudServiceDraft`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RemoteCloudServiceDraft` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HardwareSpecifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VendorManagedUpgrades` tinyint unsigned NOT NULL DEFAULT '0',
  `PricingModels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PublishedSLAs` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RemoteCloudServiceDraft`
--

LOCK TABLES `RemoteCloudServiceDraft` WRITE;
/*!40000 ALTER TABLE `RemoteCloudServiceDraft` DISABLE KEYS */;
/*!40000 ALTER TABLE `RemoteCloudServiceDraft` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RestrictedDownloadPage`
--

DROP TABLE IF EXISTS `RestrictedDownloadPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RestrictedDownloadPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GuidelinesLogoLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RestrictedDownloadPage`
--

LOCK TABLES `RestrictedDownloadPage` WRITE;
/*!40000 ALTER TABLE `RestrictedDownloadPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `RestrictedDownloadPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RestrictedDownloadPage_Live`
--

DROP TABLE IF EXISTS `RestrictedDownloadPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RestrictedDownloadPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GuidelinesLogoLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RestrictedDownloadPage_Live`
--

LOCK TABLES `RestrictedDownloadPage_Live` WRITE;
/*!40000 ALTER TABLE `RestrictedDownloadPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `RestrictedDownloadPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RestrictedDownloadPage_versions`
--

DROP TABLE IF EXISTS `RestrictedDownloadPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RestrictedDownloadPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `GuidelinesLogoLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RestrictedDownloadPage_versions`
--

LOCK TABLES `RestrictedDownloadPage_versions` WRITE;
/*!40000 ALTER TABLE `RestrictedDownloadPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `RestrictedDownloadPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RoomMetricSampleData`
--

DROP TABLE IF EXISTS `RoomMetricSampleData`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RoomMetricSampleData` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RoomMetricSampleData') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RoomMetricSampleData',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` double DEFAULT NULL,
  `TimeStamp` int NOT NULL DEFAULT '0',
  `TypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RoomMetricSampleData`
--

LOCK TABLES `RoomMetricSampleData` WRITE;
/*!40000 ALTER TABLE `RoomMetricSampleData` DISABLE KEYS */;
/*!40000 ALTER TABLE `RoomMetricSampleData` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RoomMetricType`
--

DROP TABLE IF EXISTS `RoomMetricType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RoomMetricType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RoomMetricType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RoomMetricType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` enum('Persons','CO2','Temperature','Humidity') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Persons',
  `Unit` enum('units','ppm','°F','%') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'units',
  `Endpoint` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RoomID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RoomID` (`RoomID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RoomMetricType`
--

LOCK TABLES `RoomMetricType` WRITE;
/*!40000 ALTER TABLE `RoomMetricType` DISABLE KEYS */;
/*!40000 ALTER TABLE `RoomMetricType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `RssNews`
--

DROP TABLE IF EXISTS `RssNews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `RssNews` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('RssNews') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'RssNews',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Headline` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `RssNews`
--

LOCK TABLES `RssNews` WRITE;
/*!40000 ALTER TABLE `RssNews` DISABLE KEYS */;
/*!40000 ALTER TABLE `RssNews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SchedSpeaker`
--

DROP TABLE IF EXISTS `SchedSpeaker`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SchedSpeaker` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SchedSpeaker') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SchedSpeaker',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SchedSpeaker`
--

LOCK TABLES `SchedSpeaker` WRITE;
/*!40000 ALTER TABLE `SchedSpeaker` DISABLE KEYS */;
/*!40000 ALTER TABLE `SchedSpeaker` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ScheduleCalendarSyncInfo`
--

DROP TABLE IF EXISTS `ScheduleCalendarSyncInfo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ScheduleCalendarSyncInfo` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ScheduleCalendarSyncInfo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ScheduleCalendarSyncInfo',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ExternalId` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ETag` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarEventExternalUrl` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VCard` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CalendarSyncInfoID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  `SummitEventID` int DEFAULT NULL,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Owner_SummitEvent_CalendarSyncInfo_IDX` (`OwnerID`,`SummitEventID`,`CalendarSyncInfoID`),
  KEY `CalendarSyncInfoID` (`CalendarSyncInfoID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ScheduleCalendarSyncInfo`
--

LOCK TABLES `ScheduleCalendarSyncInfo` WRITE;
/*!40000 ALTER TABLE `ScheduleCalendarSyncInfo` DISABLE KEYS */;
/*!40000 ALTER TABLE `ScheduleCalendarSyncInfo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ScheduledSummitLocationBanner`
--

DROP TABLE IF EXISTS `ScheduledSummitLocationBanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ScheduledSummitLocationBanner` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ScheduledSummitLocationBanner`
--

LOCK TABLES `ScheduledSummitLocationBanner` WRITE;
/*!40000 ALTER TABLE `ScheduledSummitLocationBanner` DISABLE KEYS */;
/*!40000 ALTER TABLE `ScheduledSummitLocationBanner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SciencePage`
--

DROP TABLE IF EXISTS `SciencePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SciencePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AmazonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BookPDFID` int DEFAULT NULL,
  `PrintPDFID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BookPDFID` (`BookPDFID`),
  KEY `PrintPDFID` (`PrintPDFID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SciencePage`
--

LOCK TABLES `SciencePage` WRITE;
/*!40000 ALTER TABLE `SciencePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SciencePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SciencePage_Live`
--

DROP TABLE IF EXISTS `SciencePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SciencePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AmazonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BookPDFID` int DEFAULT NULL,
  `PrintPDFID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BookPDFID` (`BookPDFID`),
  KEY `PrintPDFID` (`PrintPDFID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SciencePage_Live`
--

LOCK TABLES `SciencePage_Live` WRITE;
/*!40000 ALTER TABLE `SciencePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SciencePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SciencePage_versions`
--

DROP TABLE IF EXISTS `SciencePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SciencePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `AmazonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BookPDFID` int DEFAULT NULL,
  `PrintPDFID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `BookPDFID` (`BookPDFID`),
  KEY `PrintPDFID` (`PrintPDFID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SciencePage_versions`
--

LOCK TABLES `SciencePage_versions` WRITE;
/*!40000 ALTER TABLE `SciencePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SciencePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlan`
--

DROP TABLE IF EXISTS `SelectionPlan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlan` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SelectionPlan') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SelectionPlan',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  `SubmissionBeginDate` datetime DEFAULT NULL,
  `SubmissionEndDate` datetime DEFAULT NULL,
  `VotingBeginDate` datetime DEFAULT NULL,
  `VotingEndDate` datetime DEFAULT NULL,
  `SelectionBeginDate` datetime DEFAULT NULL,
  `SelectionEndDate` datetime DEFAULT NULL,
  `MaxSubmissionAllowedPerUser` int NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `AllowNewPresentations` tinyint(1) NOT NULL DEFAULT '1',
  `SubmissionPeriodDisclaimer` longtext COLLATE utf8mb4_0900_as_cs,
  `PresentationCreatorNotificationEmailTemplate` varchar(255) COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT '',
  `PresentationModeratorNotificationEmailTemplate` varchar(255) COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT '',
  `PresentationSpeakerNotificationEmailTemplate` varchar(255) COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT '',
  `SubmissionLockDownPresentationStatusDate` datetime DEFAULT NULL,
  `AllowProposedSchedules` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlan`
--

LOCK TABLES `SelectionPlan` WRITE;
/*!40000 ALTER TABLE `SelectionPlan` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlanAllowedEditablePresentationQuestion`
--

DROP TABLE IF EXISTS `SelectionPlanAllowedEditablePresentationQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlanAllowedEditablePresentationQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SelectionPlanAllowedEditablePresentationQuestion',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_SelectionPlan_SelPlanAllowedEditablePresentationQuestion` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=258 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlanAllowedEditablePresentationQuestion`
--

LOCK TABLES `SelectionPlanAllowedEditablePresentationQuestion` WRITE;
/*!40000 ALTER TABLE `SelectionPlanAllowedEditablePresentationQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlanAllowedEditablePresentationQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlanAllowedPresentationQuestion`
--

DROP TABLE IF EXISTS `SelectionPlanAllowedPresentationQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlanAllowedPresentationQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SelectionPlanAllowedPresentationQuestion',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_SelectionPlan_SelectionPlanAllowedPresentationQuestion` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=803 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlanAllowedPresentationQuestion`
--

LOCK TABLES `SelectionPlanAllowedPresentationQuestion` WRITE;
/*!40000 ALTER TABLE `SelectionPlanAllowedPresentationQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlanAllowedPresentationQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlan_AllowedMembers`
--

DROP TABLE IF EXISTS `SelectionPlan_AllowedMembers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlan_AllowedMembers` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SelectionPlanID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_UNIQUE_SelectionPlan_AllowedMembers` (`Email`,`SelectionPlanID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  KEY `SelectionPlan_AllowedMembers_Email` (`Email`),
  CONSTRAINT `FK_SelectionPlan_AllowedMembers_SP` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlan_AllowedMembers`
--

LOCK TABLES `SelectionPlan_AllowedMembers` WRITE;
/*!40000 ALTER TABLE `SelectionPlan_AllowedMembers` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlan_AllowedMembers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlan_CategoryGroups`
--

DROP TABLE IF EXISTS `SelectionPlan_CategoryGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlan_CategoryGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SelectionPlanID` int NOT NULL DEFAULT '0',
  `PresentationCategoryGroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  KEY `PresentationCategoryGroupID` (`PresentationCategoryGroupID`)
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlan_CategoryGroups`
--

LOCK TABLES `SelectionPlan_CategoryGroups` WRITE;
/*!40000 ALTER TABLE `SelectionPlan_CategoryGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlan_CategoryGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SelectionPlan_SummitEventTypes`
--

DROP TABLE IF EXISTS `SelectionPlan_SummitEventTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SelectionPlan_SummitEventTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SelectionPlanID` int NOT NULL,
  `SummitEventTypeID` int NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_3D40A743B172E6ECDF6E48FA` (`SelectionPlanID`,`SummitEventTypeID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  KEY `SummitEventTypeID` (`SummitEventTypeID`),
  CONSTRAINT `FK_3D40A743B172E6EC` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_3D40A743DF6E48FA` FOREIGN KEY (`SummitEventTypeID`) REFERENCES `SummitEventType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SelectionPlan_SummitEventTypes`
--

LOCK TABLES `SelectionPlan_SummitEventTypes` WRITE;
/*!40000 ALTER TABLE `SelectionPlan_SummitEventTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SelectionPlan_SummitEventTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SentEmail`
--

DROP TABLE IF EXISTS `SentEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SentEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SentEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SentEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `To` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `From` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Subject` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CC` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BCC` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SerializedEmail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `Created` (`Created`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SentEmail`
--

LOCK TABLES `SentEmail` WRITE;
/*!40000 ALTER TABLE `SentEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `SentEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SentEmailSendGrid`
--

DROP TABLE IF EXISTS `SentEmailSendGrid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SentEmailSendGrid` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SentEmailSendGrid') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SentEmailSendGrid',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `To` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `From` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CC` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BCC` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsSent` tinyint unsigned NOT NULL DEFAULT '0',
  `IsPlain` tinyint unsigned NOT NULL DEFAULT '0',
  `SentDate` datetime DEFAULT NULL,
  `Attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CustomHeaders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SentEmailSendGrid`
--

LOCK TABLES `SentEmailSendGrid` WRITE;
/*!40000 ALTER TABLE `SentEmailSendGrid` DISABLE KEYS */;
/*!40000 ALTER TABLE `SentEmailSendGrid` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteBannerConfigurationSetting`
--

DROP TABLE IF EXISTS `SiteBannerConfigurationSetting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteBannerConfigurationSetting` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SiteBannerConfigurationSetting') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SiteBannerConfigurationSetting',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SiteBannerMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SiteBannerButtonText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SiteBannerButtonLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SiteBannerRank` int NOT NULL DEFAULT '0',
  `Language` enum('English','Spanish','Italian','German','Portuguese','Chinese','Japanese','French') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'English',
  `SiteConfigID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SiteConfigID` (`SiteConfigID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteBannerConfigurationSetting`
--

LOCK TABLES `SiteBannerConfigurationSetting` WRITE;
/*!40000 ALTER TABLE `SiteBannerConfigurationSetting` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteBannerConfigurationSetting` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteConfig`
--

DROP TABLE IF EXISTS `SiteConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SiteConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SiteConfig',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Tagline` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Theme` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CanViewType` enum('Anyone','LoggedInUsers','OnlyTheseUsers') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Anyone',
  `CanEditType` enum('LoggedInUsers','OnlyTheseUsers') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'LoggedInUsers',
  `CanCreateTopLevelType` enum('LoggedInUsers','OnlyTheseUsers') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'LoggedInUsers',
  `DisplaySiteBanner` tinyint unsigned NOT NULL DEFAULT '0',
  `RegistrationSendMail` tinyint unsigned NOT NULL DEFAULT '0',
  `RegistrationFromMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegistrationSubjectMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegistrationHTMLMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegistrationPlainTextMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OGApplicationID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OGAdminID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteConfig`
--

LOCK TABLES `SiteConfig` WRITE;
/*!40000 ALTER TABLE `SiteConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteConfig_CreateTopLevelGroups`
--

DROP TABLE IF EXISTS `SiteConfig_CreateTopLevelGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteConfig_CreateTopLevelGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteConfigID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SiteConfigID` (`SiteConfigID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteConfig_CreateTopLevelGroups`
--

LOCK TABLES `SiteConfig_CreateTopLevelGroups` WRITE;
/*!40000 ALTER TABLE `SiteConfig_CreateTopLevelGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteConfig_CreateTopLevelGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteConfig_EditorGroups`
--

DROP TABLE IF EXISTS `SiteConfig_EditorGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteConfig_EditorGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteConfigID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SiteConfigID` (`SiteConfigID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteConfig_EditorGroups`
--

LOCK TABLES `SiteConfig_EditorGroups` WRITE;
/*!40000 ALTER TABLE `SiteConfig_EditorGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteConfig_EditorGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteConfig_ViewerGroups`
--

DROP TABLE IF EXISTS `SiteConfig_ViewerGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteConfig_ViewerGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteConfigID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SiteConfigID` (`SiteConfigID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteConfig_ViewerGroups`
--

LOCK TABLES `SiteConfig_ViewerGroups` WRITE;
/*!40000 ALTER TABLE `SiteConfig_ViewerGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteConfig_ViewerGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree`
--

DROP TABLE IF EXISTS `SiteTree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SiteTree','Page','AnniversaryPage','ArticleHolder','ArticlePage','BioPage','BoardOfDirectorsPage','BrandingPage','CoaPage','CommPage','CommunityPageBis','CommunityPage','CompaniesPage','CompanyListPage','ConferenceLivePage','ConferenceNewsPage','ConferencePage','ConferenceSubPage','DirectAfterLoginPage','HallOfInnovation','HomePage','InteropPage','IVotedPage','LandingPageCn','LandingPage','LegalDocumentPage','LegalDocumentsHolder','LogoDownloadPage','LogoGuidelinesPage','LogoRightsPage','NewCompanyListPage','OneColumn','OpenStackFoundationStaffPage','OpenstackUser','OSLogoProgramPage','PdfPage','PresentationCategoryPage','PrimaryLogoPage','PrivacyPage','ProductPage','PTGDynamic','PTGfaq','PTG','RestrictedDownloadPage','SponsorsPage','StartPageHolder','StartPage','swagStore','TechnicalCommitteePage','UserCommitteePage','WebBadgeDownloadPage','SangriaPage','TrackChairsPage','SummitVideoApp','PresentationVotingPage','ErrorPage','RedirectorPage','VirtualPage','COALandingPage','COAVerifyPage','EventHolder','HackathonsPage','OpenStackDaysPage','SigninPage','AboutMascots','AnalystLanding','AppDevHomePage','AutomotiveLandingPage','BareMetalPage','ContainersPage2','ContainersPage','EdgeComputingPage','EnterpriseBigDataPage','EnterpriseForrester','EnterpriseHomePage','EnterpriseLegacyPage','EnterpriseWorkloadPage','ISVHomePage','LearnPage','SciencePage','SecurityPage','TelecomHomePage','MarketingPage','EditProfilePage','RegistrationPage','SpeakerVotingRegistrationPage','SoftwareHomePage','SoftwareSubPage','SpeakerListPage','EmailUtilsPage','GeneralEventsLandingPage','GeneralSummitLandingPage','PresentationVideoPage','SchedToolsPage','SummitPage','EventContextPage','NewSchedulePage','OpenDevStaticVancouverPage','PresentationPage','StaticSummitAboutPage','SummitAboutPage','SummitAppReviewPage','SummitAppSchedPage','SummitAppVenuesPage','OpenDevStaticVancouverAppVenuesPage','SummitBostonLanding','SummitCategoriesPage','OpenDevStaticVancouverCategoriesPage','SummitConfirmSpeakerPage','SummitContextPage','SummitFutureLanding','EventsFutureLandingPage','SummitHighlightsPage','SummitHomePage','SummitLocationPage','OpenDevStaticVancouverLocationPage','SummitNewStaticAboutPage','SummitOverviewPage','SummitQuestionsPage','OpenDevStaticVancouverQuestionsPage','SummitSpeakersPage','SummitSpeakerVotingPage','SummitSponsorPage','OpenDevStaticVancouverSponsorPage','SummitStaticAboutBerlinPage','SummitStaticAboutBostonPage','SummitStaticAboutPage','SummitStaticAcademyPage','SummitStaticAustinGuidePage','SummitStaticBarcelonaGuidePage','SummitStaticBostonCityGuide','SummitStaticCategoriesPage','SummitStaticDenverPage','SummitStaticDiversityPage','SummitStaticOpenSourceDays','SummitStaticShangaiPage','SummitStaticSponsorPage','SummitUpdatesPage','SummitSimplePage','UserStoriesPage','UserStoriesStatic','ElectionPage','ElectionsHolderPage','ElectionVoterPage','EventRegistrationRequestPage','JobHolder','JobRegistrationRequestPage','MarketPlaceAdminPage','MarketPlacePage','MarketPlaceDirectoryPage','BooksDirectoryPage','ConsultantsDirectoryPage','DistributionsDirectoryPage','MarketPlaceDriverPage','PrivateCloudsDirectoryPage','PublicCloudsDirectoryPage','RemoteCloudsDirectoryPage','TrainingDirectoryPage','MarketPlaceLandingPage','PublicCloudPassportsPage','MemberListPage','PaperViewerPage','SurveyPage','UserSurveyPage','SurveyReportPage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SiteTree',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `URLSegment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MenuTitle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MetaDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraMeta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowInMenus` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowInSearch` tinyint unsigned NOT NULL DEFAULT '0',
  `Sort` int NOT NULL DEFAULT '0',
  `HasBrokenFile` tinyint unsigned NOT NULL DEFAULT '0',
  `HasBrokenLink` tinyint unsigned NOT NULL DEFAULT '0',
  `ReportClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CanViewType` enum('Anyone','LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `CanEditType` enum('LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Version` int NOT NULL DEFAULT '0',
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentID` (`ParentID`),
  KEY `URLSegment` (`URLSegment`),
  KEY `Sort` (`Sort`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree`
--

LOCK TABLES `SiteTree` WRITE;
/*!40000 ALTER TABLE `SiteTree` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_EditorGroups`
--

DROP TABLE IF EXISTS `SiteTree_EditorGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_EditorGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteTreeID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SiteTreeID` (`SiteTreeID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_EditorGroups`
--

LOCK TABLES `SiteTree_EditorGroups` WRITE;
/*!40000 ALTER TABLE `SiteTree_EditorGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_EditorGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_ImageTracking`
--

DROP TABLE IF EXISTS `SiteTree_ImageTracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_ImageTracking` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteTreeID` int NOT NULL DEFAULT '0',
  `FileID` int NOT NULL DEFAULT '0',
  `FieldName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SiteTreeID` (`SiteTreeID`),
  KEY `FileID` (`FileID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_ImageTracking`
--

LOCK TABLES `SiteTree_ImageTracking` WRITE;
/*!40000 ALTER TABLE `SiteTree_ImageTracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_ImageTracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_LinkTracking`
--

DROP TABLE IF EXISTS `SiteTree_LinkTracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_LinkTracking` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteTreeID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  `FieldName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SiteTreeID` (`SiteTreeID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_LinkTracking`
--

LOCK TABLES `SiteTree_LinkTracking` WRITE;
/*!40000 ALTER TABLE `SiteTree_LinkTracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_LinkTracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_Live`
--

DROP TABLE IF EXISTS `SiteTree_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SiteTree','Page','AnniversaryPage','ArticleHolder','ArticlePage','BioPage','BoardOfDirectorsPage','BrandingPage','CoaPage','CommPage','CommunityPageBis','CommunityPage','CompaniesPage','CompanyListPage','ConferenceLivePage','ConferenceNewsPage','ConferencePage','ConferenceSubPage','DirectAfterLoginPage','HallOfInnovation','HomePage','InteropPage','IVotedPage','LandingPageCn','LandingPage','LegalDocumentPage','LegalDocumentsHolder','LogoDownloadPage','LogoGuidelinesPage','LogoRightsPage','NewCompanyListPage','OneColumn','OpenStackFoundationStaffPage','OpenstackUser','OSLogoProgramPage','PdfPage','PresentationCategoryPage','PrimaryLogoPage','PrivacyPage','ProductPage','PTGDynamic','PTGfaq','PTG','RestrictedDownloadPage','SponsorsPage','StartPageHolder','StartPage','swagStore','TechnicalCommitteePage','UserCommitteePage','WebBadgeDownloadPage','SangriaPage','TrackChairsPage','SummitVideoApp','PresentationVotingPage','ErrorPage','RedirectorPage','VirtualPage','COALandingPage','COAVerifyPage','EventHolder','HackathonsPage','OpenStackDaysPage','SigninPage','AboutMascots','AnalystLanding','AppDevHomePage','AutomotiveLandingPage','BareMetalPage','ContainersPage2','ContainersPage','EdgeComputingPage','EnterpriseBigDataPage','EnterpriseForrester','EnterpriseHomePage','EnterpriseLegacyPage','EnterpriseWorkloadPage','ISVHomePage','LearnPage','SciencePage','SecurityPage','TelecomHomePage','MarketingPage','EditProfilePage','RegistrationPage','SpeakerVotingRegistrationPage','SoftwareHomePage','SoftwareSubPage','SpeakerListPage','EmailUtilsPage','GeneralEventsLandingPage','GeneralSummitLandingPage','PresentationVideoPage','SchedToolsPage','SummitPage','EventContextPage','NewSchedulePage','OpenDevStaticVancouverPage','PresentationPage','StaticSummitAboutPage','SummitAboutPage','SummitAppReviewPage','SummitAppSchedPage','SummitAppVenuesPage','OpenDevStaticVancouverAppVenuesPage','SummitBostonLanding','SummitCategoriesPage','OpenDevStaticVancouverCategoriesPage','SummitConfirmSpeakerPage','SummitContextPage','SummitFutureLanding','EventsFutureLandingPage','SummitHighlightsPage','SummitHomePage','SummitLocationPage','OpenDevStaticVancouverLocationPage','SummitNewStaticAboutPage','SummitOverviewPage','SummitQuestionsPage','OpenDevStaticVancouverQuestionsPage','SummitSpeakersPage','SummitSpeakerVotingPage','SummitSponsorPage','OpenDevStaticVancouverSponsorPage','SummitStaticAboutBerlinPage','SummitStaticAboutBostonPage','SummitStaticAboutPage','SummitStaticAcademyPage','SummitStaticAustinGuidePage','SummitStaticBarcelonaGuidePage','SummitStaticBostonCityGuide','SummitStaticCategoriesPage','SummitStaticDenverPage','SummitStaticDiversityPage','SummitStaticOpenSourceDays','SummitStaticShangaiPage','SummitStaticSponsorPage','SummitUpdatesPage','SummitSimplePage','UserStoriesPage','UserStoriesStatic','ElectionPage','ElectionsHolderPage','ElectionVoterPage','EventRegistrationRequestPage','JobHolder','JobRegistrationRequestPage','MarketPlaceAdminPage','MarketPlacePage','MarketPlaceDirectoryPage','BooksDirectoryPage','ConsultantsDirectoryPage','DistributionsDirectoryPage','MarketPlaceDriverPage','PrivateCloudsDirectoryPage','PublicCloudsDirectoryPage','RemoteCloudsDirectoryPage','TrainingDirectoryPage','MarketPlaceLandingPage','PublicCloudPassportsPage','MemberListPage','PaperViewerPage','SurveyPage','UserSurveyPage','SurveyReportPage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SiteTree',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `URLSegment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MenuTitle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MetaDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraMeta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowInMenus` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowInSearch` tinyint unsigned NOT NULL DEFAULT '0',
  `Sort` int NOT NULL DEFAULT '0',
  `HasBrokenFile` tinyint unsigned NOT NULL DEFAULT '0',
  `HasBrokenLink` tinyint unsigned NOT NULL DEFAULT '0',
  `ReportClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CanViewType` enum('Anyone','LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `CanEditType` enum('LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Version` int NOT NULL DEFAULT '0',
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentID` (`ParentID`),
  KEY `URLSegment` (`URLSegment`),
  KEY `Sort` (`Sort`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_Live`
--

LOCK TABLES `SiteTree_Live` WRITE;
/*!40000 ALTER TABLE `SiteTree_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_ViewerGroups`
--

DROP TABLE IF EXISTS `SiteTree_ViewerGroups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_ViewerGroups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SiteTreeID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SiteTreeID` (`SiteTreeID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_ViewerGroups`
--

LOCK TABLES `SiteTree_ViewerGroups` WRITE;
/*!40000 ALTER TABLE `SiteTree_ViewerGroups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_ViewerGroups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SiteTree_versions`
--

DROP TABLE IF EXISTS `SiteTree_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SiteTree_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `WasPublished` tinyint unsigned NOT NULL DEFAULT '0',
  `AuthorID` int NOT NULL DEFAULT '0',
  `PublisherID` int NOT NULL DEFAULT '0',
  `ClassName` enum('SiteTree','Page','AnniversaryPage','ArticleHolder','ArticlePage','BioPage','BoardOfDirectorsPage','BrandingPage','CoaPage','CommPage','CommunityPageBis','CommunityPage','CompaniesPage','CompanyListPage','ConferenceLivePage','ConferenceNewsPage','ConferencePage','ConferenceSubPage','DirectAfterLoginPage','HallOfInnovation','HomePage','InteropPage','IVotedPage','LandingPageCn','LandingPage','LegalDocumentPage','LegalDocumentsHolder','LogoDownloadPage','LogoGuidelinesPage','LogoRightsPage','NewCompanyListPage','OneColumn','OpenStackFoundationStaffPage','OpenstackUser','OSLogoProgramPage','PdfPage','PresentationCategoryPage','PrimaryLogoPage','PrivacyPage','ProductPage','PTGDynamic','PTGfaq','PTG','RestrictedDownloadPage','SponsorsPage','StartPageHolder','StartPage','swagStore','TechnicalCommitteePage','UserCommitteePage','WebBadgeDownloadPage','SangriaPage','TrackChairsPage','SummitVideoApp','PresentationVotingPage','ErrorPage','RedirectorPage','VirtualPage','COALandingPage','COAVerifyPage','EventHolder','HackathonsPage','OpenStackDaysPage','SigninPage','AboutMascots','AnalystLanding','AppDevHomePage','AutomotiveLandingPage','BareMetalPage','ContainersPage2','ContainersPage','EdgeComputingPage','EnterpriseBigDataPage','EnterpriseForrester','EnterpriseHomePage','EnterpriseLegacyPage','EnterpriseWorkloadPage','ISVHomePage','LearnPage','SciencePage','SecurityPage','TelecomHomePage','MarketingPage','EditProfilePage','RegistrationPage','SpeakerVotingRegistrationPage','SoftwareHomePage','SoftwareSubPage','SpeakerListPage','EmailUtilsPage','GeneralEventsLandingPage','GeneralSummitLandingPage','PresentationVideoPage','SchedToolsPage','SummitPage','EventContextPage','NewSchedulePage','OpenDevStaticVancouverPage','PresentationPage','StaticSummitAboutPage','SummitAboutPage','SummitAppReviewPage','SummitAppSchedPage','SummitAppVenuesPage','OpenDevStaticVancouverAppVenuesPage','SummitBostonLanding','SummitCategoriesPage','OpenDevStaticVancouverCategoriesPage','SummitConfirmSpeakerPage','SummitContextPage','SummitFutureLanding','EventsFutureLandingPage','SummitHighlightsPage','SummitHomePage','SummitLocationPage','OpenDevStaticVancouverLocationPage','SummitNewStaticAboutPage','SummitOverviewPage','SummitQuestionsPage','OpenDevStaticVancouverQuestionsPage','SummitSpeakersPage','SummitSpeakerVotingPage','SummitSponsorPage','OpenDevStaticVancouverSponsorPage','SummitStaticAboutBerlinPage','SummitStaticAboutBostonPage','SummitStaticAboutPage','SummitStaticAcademyPage','SummitStaticAustinGuidePage','SummitStaticBarcelonaGuidePage','SummitStaticBostonCityGuide','SummitStaticCategoriesPage','SummitStaticDenverPage','SummitStaticDiversityPage','SummitStaticOpenSourceDays','SummitStaticShangaiPage','SummitStaticSponsorPage','SummitUpdatesPage','SummitSimplePage','UserStoriesPage','UserStoriesStatic','ElectionPage','ElectionsHolderPage','ElectionVoterPage','EventRegistrationRequestPage','JobHolder','JobRegistrationRequestPage','MarketPlaceAdminPage','MarketPlacePage','MarketPlaceDirectoryPage','BooksDirectoryPage','ConsultantsDirectoryPage','DistributionsDirectoryPage','MarketPlaceDriverPage','PrivateCloudsDirectoryPage','PublicCloudsDirectoryPage','RemoteCloudsDirectoryPage','TrainingDirectoryPage','MarketPlaceLandingPage','PublicCloudPassportsPage','MemberListPage','PaperViewerPage','SurveyPage','UserSurveyPage','SurveyReportPage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SiteTree',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `URLSegment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MenuTitle` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MetaDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtraMeta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowInMenus` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowInSearch` tinyint unsigned NOT NULL DEFAULT '0',
  `Sort` int NOT NULL DEFAULT '0',
  `HasBrokenFile` tinyint unsigned NOT NULL DEFAULT '0',
  `HasBrokenLink` tinyint unsigned NOT NULL DEFAULT '0',
  `ReportClass` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CanViewType` enum('Anyone','LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `CanEditType` enum('LoggedInUsers','OnlyTheseUsers','Inherit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Inherit',
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `AuthorID` (`AuthorID`),
  KEY `PublisherID` (`PublisherID`),
  KEY `ParentID` (`ParentID`),
  KEY `URLSegment` (`URLSegment`),
  KEY `Sort` (`Sort`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SiteTree_versions`
--

LOCK TABLES `SiteTree_versions` WRITE;
/*!40000 ALTER TABLE `SiteTree_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SiteTree_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SoftwareHomePage`
--

DROP TABLE IF EXISTS `SoftwareHomePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SoftwareHomePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IntroTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroTitle2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SoftwareHomePage`
--

LOCK TABLES `SoftwareHomePage` WRITE;
/*!40000 ALTER TABLE `SoftwareHomePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SoftwareHomePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SoftwareHomePageSubMenuItem`
--

DROP TABLE IF EXISTS `SoftwareHomePageSubMenuItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SoftwareHomePageSubMenuItem` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SoftwareHomePageSubMenuItem') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SoftwareHomePageSubMenuItem',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `ParentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ParentID` (`ParentID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SoftwareHomePageSubMenuItem`
--

LOCK TABLES `SoftwareHomePageSubMenuItem` WRITE;
/*!40000 ALTER TABLE `SoftwareHomePageSubMenuItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `SoftwareHomePageSubMenuItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SoftwareHomePage_Live`
--

DROP TABLE IF EXISTS `SoftwareHomePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SoftwareHomePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IntroTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroTitle2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SoftwareHomePage_Live`
--

LOCK TABLES `SoftwareHomePage_Live` WRITE;
/*!40000 ALTER TABLE `SoftwareHomePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SoftwareHomePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SoftwareHomePage_versions`
--

DROP TABLE IF EXISTS `SoftwareHomePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SoftwareHomePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `IntroTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroTitle2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IntroText2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SoftwareHomePage_versions`
--

LOCK TABLES `SoftwareHomePage_versions` WRITE;
/*!40000 ALTER TABLE `SoftwareHomePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SoftwareHomePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerActiveInvolvement`
--

DROP TABLE IF EXISTS `SpeakerActiveInvolvement`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerActiveInvolvement` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerActiveInvolvement') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerActiveInvolvement',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Involvement` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerActiveInvolvement`
--

LOCK TABLES `SpeakerActiveInvolvement` WRITE;
/*!40000 ALTER TABLE `SpeakerActiveInvolvement` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerActiveInvolvement` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerAnnouncementSummitEmail`
--

DROP TABLE IF EXISTS `SpeakerAnnouncementSummitEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerAnnouncementSummitEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerAnnouncementSummitEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerAnnouncementSummitEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `AnnouncementEmailTypeSent` enum('ACCEPTED','REJECTED','ALTERNATE','ACCEPTED_ALTERNATE','ACCEPTED_REJECTED','ALTERNATE_REJECTED','SECOND_BREAKOUT_REMINDER','SECOND_BREAKOUT_REGISTER','CREATE_MEMBERSHIP','NONE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NONE',
  `AnnouncementEmailSentDate` datetime DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=1582 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerAnnouncementSummitEmail`
--

LOCK TABLES `SpeakerAnnouncementSummitEmail` WRITE;
/*!40000 ALTER TABLE `SpeakerAnnouncementSummitEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerAnnouncementSummitEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerContactEmail`
--

DROP TABLE IF EXISTS `SpeakerContactEmail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerContactEmail` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerContactEmail') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerContactEmail',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `OrgName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OrgEmail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EventName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Format` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Attendance` int NOT NULL DEFAULT '0',
  `DateOfEvent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Topics` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `GeneralRequest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EmailSent` tinyint unsigned NOT NULL DEFAULT '0',
  `RecipientID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RecipientID` (`RecipientID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerContactEmail`
--

LOCK TABLES `SpeakerContactEmail` WRITE;
/*!40000 ALTER TABLE `SpeakerContactEmail` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerContactEmail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerCreationEmailCreationRequest`
--

DROP TABLE IF EXISTS `SpeakerCreationEmailCreationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerCreationEmailCreationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerCreationEmailCreationRequest`
--

LOCK TABLES `SpeakerCreationEmailCreationRequest` WRITE;
/*!40000 ALTER TABLE `SpeakerCreationEmailCreationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerCreationEmailCreationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerEditPermissionRequest`
--

DROP TABLE IF EXISTS `SpeakerEditPermissionRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerEditPermissionRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerEditPermissionRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerEditPermissionRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `ApprovedDate` datetime DEFAULT NULL,
  `CreatedDate` datetime DEFAULT NULL,
  `Hash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SpeakerID` int DEFAULT NULL,
  `RequestedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `RequestedByID` (`RequestedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=1783 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerEditPermissionRequest`
--

LOCK TABLES `SpeakerEditPermissionRequest` WRITE;
/*!40000 ALTER TABLE `SpeakerEditPermissionRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerEditPermissionRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerExpertise`
--

DROP TABLE IF EXISTS `SpeakerExpertise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerExpertise` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerExpertise') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerExpertise',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Expertise` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=4893 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerExpertise`
--

LOCK TABLES `SpeakerExpertise` WRITE;
/*!40000 ALTER TABLE `SpeakerExpertise` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerExpertise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerOrganizationalRole`
--

DROP TABLE IF EXISTS `SpeakerOrganizationalRole`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerOrganizationalRole` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerOrganizationalRole') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerOrganizationalRole',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Role` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerOrganizationalRole`
--

LOCK TABLES `SpeakerOrganizationalRole` WRITE;
/*!40000 ALTER TABLE `SpeakerOrganizationalRole` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerOrganizationalRole` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerPresentationLink`
--

DROP TABLE IF EXISTS `SpeakerPresentationLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerPresentationLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerPresentationLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerPresentationLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `LinkUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=808 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerPresentationLink`
--

LOCK TABLES `SpeakerPresentationLink` WRITE;
/*!40000 ALTER TABLE `SpeakerPresentationLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerPresentationLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerRegistrationRequest`
--

DROP TABLE IF EXISTS `SpeakerRegistrationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerRegistrationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerRegistrationRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerRegistrationRequest',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `IsConfirmed` tinyint unsigned NOT NULL DEFAULT '0',
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ConfirmationDate` datetime DEFAULT NULL,
  `ConfirmationHash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProposerID` int DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Email` (`Email`),
  KEY `ProposerID` (`ProposerID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=1052 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerRegistrationRequest`
--

LOCK TABLES `SpeakerRegistrationRequest` WRITE;
/*!40000 ALTER TABLE `SpeakerRegistrationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerRegistrationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerSelectionAnnouncementEmailCreationRequest`
--

DROP TABLE IF EXISTS `SpeakerSelectionAnnouncementEmailCreationRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerSelectionAnnouncementEmailCreationRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Type` enum('ACCEPTED','ACCEPTED_ALTERNATE','ACCEPTED_REJECTED','ALTERNATE','ALTERNATE_REJECTED') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ACCEPTED',
  `SpeakerRole` enum('SPEAKER','MODERATOR') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SPEAKER',
  `PromoCodeID` int DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PromoCodeID` (`PromoCodeID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerSelectionAnnouncementEmailCreationRequest`
--

LOCK TABLES `SpeakerSelectionAnnouncementEmailCreationRequest` WRITE;
/*!40000 ALTER TABLE `SpeakerSelectionAnnouncementEmailCreationRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerSelectionAnnouncementEmailCreationRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerSummitRegistrationDiscountCode`
--

DROP TABLE IF EXISTS `SpeakerSummitRegistrationDiscountCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerSummitRegistrationDiscountCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Type` enum('ACCEPTED','ALTERNATE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ACCEPTED',
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  CONSTRAINT `FK_335080B611D3633A` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerSummitRegistrationDiscountCode`
--

LOCK TABLES `SpeakerSummitRegistrationDiscountCode` WRITE;
/*!40000 ALTER TABLE `SpeakerSummitRegistrationDiscountCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerSummitRegistrationDiscountCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerSummitRegistrationPromoCode`
--

DROP TABLE IF EXISTS `SpeakerSummitRegistrationPromoCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerSummitRegistrationPromoCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Type` enum('ACCEPTED','ALTERNATE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ACCEPTED',
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  CONSTRAINT `FK_2E203D4011D3633A` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=702 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerSummitRegistrationPromoCode`
--

LOCK TABLES `SpeakerSummitRegistrationPromoCode` WRITE;
/*!40000 ALTER TABLE `SpeakerSummitRegistrationPromoCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerSummitRegistrationPromoCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerSummitState`
--

DROP TABLE IF EXISTS `SpeakerSummitState`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerSummitState` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerSummitState') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerSummitState',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Event` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerSummitState`
--

LOCK TABLES `SpeakerSummitState` WRITE;
/*!40000 ALTER TABLE `SpeakerSummitState` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerSummitState` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpeakerTravelPreference`
--

DROP TABLE IF EXISTS `SpeakerTravelPreference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpeakerTravelPreference` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpeakerTravelPreference') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpeakerTravelPreference',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SpeakerID` (`SpeakerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpeakerTravelPreference`
--

LOCK TABLES `SpeakerTravelPreference` WRITE;
/*!40000 ALTER TABLE `SpeakerTravelPreference` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpeakerTravelPreference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SpokenLanguage`
--

DROP TABLE IF EXISTS `SpokenLanguage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SpokenLanguage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SpokenLanguage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SpokenLanguage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SpokenLanguage`
--

LOCK TABLES `SpokenLanguage` WRITE;
/*!40000 ALTER TABLE `SpokenLanguage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SpokenLanguage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sponsor`
--

DROP TABLE IF EXISTS `Sponsor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sponsor` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Sponsor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Sponsor',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SubmitPageUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '1',
  `CompanyID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `IsPublished` tinyint(1) NOT NULL DEFAULT '1',
  `SideImageID` int DEFAULT NULL,
  `HeaderImageID` int DEFAULT NULL,
  `Marquee` varchar(150) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Intro` varchar(1000) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalLink` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VideoLink` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ChatLink` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ShowLogoInEventPage` tinyint(1) NOT NULL DEFAULT '1',
  `SideImageAltText` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeaderImageAltText` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeaderImageMobileAltText` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CarouselAdvertiseImageAltText` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `FeaturedEventID` int DEFAULT NULL,
  `HeaderImageMobileID` int DEFAULT NULL,
  `CarouselAdvertiseImageID` int DEFAULT NULL,
  `SummitSponsorshipTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `SideImageID` (`SideImageID`),
  KEY `HeaderImageID` (`HeaderImageID`),
  KEY `FeaturedEventID` (`FeaturedEventID`),
  KEY `HeaderImageMobileID` (`HeaderImageMobileID`),
  KEY `CarouselAdvertiseImageID` (`CarouselAdvertiseImageID`),
  KEY `SummitSponsorshipTypeID` (`SummitSponsorshipTypeID`),
  CONSTRAINT `FK_Sponsor_Carousel_Advertise_Image` FOREIGN KEY (`CarouselAdvertiseImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Sponsor_Featured_Event` FOREIGN KEY (`FeaturedEventID`) REFERENCES `SummitEvent` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Sponsor_Header_Image` FOREIGN KEY (`HeaderImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Sponsor_Header_Image_Mobile` FOREIGN KEY (`HeaderImageMobileID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Sponsor_Side_Image` FOREIGN KEY (`SideImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Sponsor_SummitSponsorshipType` FOREIGN KEY (`SummitSponsorshipTypeID`) REFERENCES `Summit_SponsorshipType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SponsorCompany` FOREIGN KEY (`CompanyID`) REFERENCES `Company` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Sponsor`
--

LOCK TABLES `Sponsor` WRITE;
/*!40000 ALTER TABLE `Sponsor` DISABLE KEYS */;
/*!40000 ALTER TABLE `Sponsor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorAd`
--

DROP TABLE IF EXISTS `SponsorAd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorAd` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SponsorAd',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Alt` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CustomOrder` int NOT NULL DEFAULT '1',
  `SponsorID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SponsorID` (`SponsorID`),
  KEY `ImageID` (`ImageID`),
  CONSTRAINT `FK_SponsorAd_Image` FOREIGN KEY (`ImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SponsorAd_Sponsor` FOREIGN KEY (`SponsorID`) REFERENCES `Sponsor` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorAd`
--

LOCK TABLES `SponsorAd` WRITE;
/*!40000 ALTER TABLE `SponsorAd` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorAd` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorBadgeScan`
--

DROP TABLE IF EXISTS `SponsorBadgeScan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorBadgeScan` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SponsorBadgeScan') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SponsorBadgeScan',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `QRCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ScanDate` datetime DEFAULT NULL,
  `UserID` int DEFAULT NULL,
  `BadgeID` int DEFAULT NULL,
  `Notes` varchar(1024) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `BadgeID` (`BadgeID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_SponsorBadgeScan_SponsorUserInfoGrant` FOREIGN KEY (`ID`) REFERENCES `SponsorUserInfoGrant` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10273 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorBadgeScan`
--

LOCK TABLES `SponsorBadgeScan` WRITE;
/*!40000 ALTER TABLE `SponsorBadgeScan` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorBadgeScan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorMaterial`
--

DROP TABLE IF EXISTS `SponsorMaterial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorMaterial` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SponsorMaterial',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Type` enum('Video','Link','Slide') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Slide',
  `CustomOrder` int NOT NULL DEFAULT '1',
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SponsorID` (`SponsorID`),
  CONSTRAINT `FK_SponsorMaterial_Sponsor` FOREIGN KEY (`SponsorID`) REFERENCES `Sponsor` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorMaterial`
--

LOCK TABLES `SponsorMaterial` WRITE;
/*!40000 ALTER TABLE `SponsorMaterial` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorMaterial` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorSocialNetwork`
--

DROP TABLE IF EXISTS `SponsorSocialNetwork`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorSocialNetwork` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SponsorSocialNetwork',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IconCSSClass` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsEnable` tinyint(1) NOT NULL DEFAULT '1',
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SponsorID` (`SponsorID`),
  CONSTRAINT `FK_SponsorSocialNetwork_Sponsor` FOREIGN KEY (`SponsorID`) REFERENCES `Sponsor` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorSocialNetwork`
--

LOCK TABLES `SponsorSocialNetwork` WRITE;
/*!40000 ALTER TABLE `SponsorSocialNetwork` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorSocialNetwork` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorSummitRegistrationDiscountCode`
--

DROP TABLE IF EXISTS `SponsorSummitRegistrationDiscountCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorSummitRegistrationDiscountCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SponsorID` (`SponsorID`),
  CONSTRAINT `FK_SponsorSummitRegistrationDiscountCode_PromoCode` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorSummitRegistrationDiscountCode`
--

LOCK TABLES `SponsorSummitRegistrationDiscountCode` WRITE;
/*!40000 ALTER TABLE `SponsorSummitRegistrationDiscountCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorSummitRegistrationDiscountCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorSummitRegistrationPromoCode`
--

DROP TABLE IF EXISTS `SponsorSummitRegistrationPromoCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorSummitRegistrationPromoCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SponsorID` (`SponsorID`),
  CONSTRAINT `FK_SponsorSummitRegistrationPromoCode_PromoCode` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorSummitRegistrationPromoCode`
--

LOCK TABLES `SponsorSummitRegistrationPromoCode` WRITE;
/*!40000 ALTER TABLE `SponsorSummitRegistrationPromoCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorSummitRegistrationPromoCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorUserInfoGrant`
--

DROP TABLE IF EXISTS `SponsorUserInfoGrant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorUserInfoGrant` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime DEFAULT NULL,
  `LastEdited` datetime DEFAULT NULL,
  `ClassName` enum('SponsorUserInfoGrant','SponsorBadgeScan') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SponsorUserInfoGrant',
  `AllowedUserID` int DEFAULT NULL,
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `AllowedUserID` (`AllowedUserID`),
  KEY `SponsorID` (`SponsorID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_39DC8CF694CE1A1A` FOREIGN KEY (`SponsorID`) REFERENCES `Sponsor` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_39DC8CF6A293D583` FOREIGN KEY (`AllowedUserID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10273 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorUserInfoGrant`
--

LOCK TABLES `SponsorUserInfoGrant` WRITE;
/*!40000 ALTER TABLE `SponsorUserInfoGrant` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorUserInfoGrant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Sponsor_Users`
--

DROP TABLE IF EXISTS `Sponsor_Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Sponsor_Users` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SponsorID` (`SponsorID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB AUTO_INCREMENT=214 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Sponsor_Users`
--

LOCK TABLES `Sponsor_Users` WRITE;
/*!40000 ALTER TABLE `Sponsor_Users` DISABLE KEYS */;
/*!40000 ALTER TABLE `Sponsor_Users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsoredProject`
--

DROP TABLE IF EXISTS `SponsoredProject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsoredProject` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SponsoredProject',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `IsActive` tinyint(1) NOT NULL,
  `ShouldShowOnNavBar` tinyint(1) NOT NULL DEFAULT '1',
  `SiteURL` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LogoID` int DEFAULT NULL,
  `ParentProjectID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_785938A7FE11D138` (`Name`),
  UNIQUE KEY `UNIQ_785938A738AF345C` (`Slug`),
  KEY `LogoID` (`LogoID`),
  KEY `ParentProjectID` (`ParentProjectID`),
  CONSTRAINT `FK_785938A7674AB94A` FOREIGN KEY (`LogoID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_Parent_SubProject` FOREIGN KEY (`ParentProjectID`) REFERENCES `SponsoredProject` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsoredProject`
--

LOCK TABLES `SponsoredProject` WRITE;
/*!40000 ALTER TABLE `SponsoredProject` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsoredProject` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorsPage_Companies`
--

DROP TABLE IF EXISTS `SponsorsPage_Companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorsPage_Companies` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorsPageID` int NOT NULL DEFAULT '0',
  `CompanyID` int NOT NULL DEFAULT '0',
  `SponsorshipType` enum('Headline','Premier','Event','Startup','InKind','Spotlight','Media') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Startup',
  `SubmitPageUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LogoSize` enum('None','Small','Medium','Large','Big') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  PRIMARY KEY (`ID`),
  KEY `SponsorsPageID` (`SponsorsPageID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorsPage_Companies`
--

LOCK TABLES `SponsorsPage_Companies` WRITE;
/*!40000 ALTER TABLE `SponsorsPage_Companies` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorsPage_Companies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SponsorshipType`
--

DROP TABLE IF EXISTS `SponsorshipType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SponsorshipType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SponsorshipType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SponsorshipType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Size` enum('Small','Medium','Large','Big') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Medium',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SponsorshipType`
--

LOCK TABLES `SponsorshipType` WRITE;
/*!40000 ALTER TABLE `SponsorshipType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SponsorshipType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StartPage`
--

DROP TABLE IF EXISTS `StartPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StartPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StartPage`
--

LOCK TABLES `StartPage` WRITE;
/*!40000 ALTER TABLE `StartPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `StartPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StartPage_Live`
--

DROP TABLE IF EXISTS `StartPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StartPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StartPage_Live`
--

LOCK TABLES `StartPage_Live` WRITE;
/*!40000 ALTER TABLE `StartPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `StartPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StartPage_versions`
--

DROP TABLE IF EXISTS `StartPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StartPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `Summary` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StartPage_versions`
--

LOCK TABLES `StartPage_versions` WRITE;
/*!40000 ALTER TABLE `StartPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `StartPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `StripePaymentProfile`
--

DROP TABLE IF EXISTS `StripePaymentProfile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `StripePaymentProfile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsTestModeEnabled` tinyint(1) NOT NULL DEFAULT '0',
  `LiveSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LivePublishableKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LiveWebHookSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LiveWebHookId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestPublishableKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestWebHookSecretKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TestWebHookId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SendEmailReceipt` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_1AEAFB5011D3633A` FOREIGN KEY (`ID`) REFERENCES `PaymentGatewayProfile` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `StripePaymentProfile`
--

LOCK TABLES `StripePaymentProfile` WRITE;
/*!40000 ALTER TABLE `StripePaymentProfile` DISABLE KEYS */;
/*!40000 ALTER TABLE `StripePaymentProfile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SubQuestionRule`
--

DROP TABLE IF EXISTS `SubQuestionRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SubQuestionRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Visibility` enum('Visible','NotVisible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Visible',
  `VisibilityCondition` enum('Equal','NotEqual') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Equal',
  `AnswerValues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `AnswerValuesOperator` enum('Or','And') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Or',
  `ParentQuestionID` int NOT NULL,
  `SubQuestionID` int NOT NULL,
  `CustomOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_B025D976949CB82CD39BE1F4` (`ParentQuestionID`,`SubQuestionID`),
  KEY `ParentQuestionID` (`ParentQuestionID`),
  KEY `SubQuestionID` (`SubQuestionID`),
  CONSTRAINT `FK_SubQuestionRule_ParentQuestion` FOREIGN KEY (`ParentQuestionID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SubQuestionRule_SubQuestion` FOREIGN KEY (`SubQuestionID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SubQuestionRule`
--

LOCK TABLES `SubQuestionRule` WRITE;
/*!40000 ALTER TABLE `SubQuestionRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SubQuestionRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Submitter`
--

DROP TABLE IF EXISTS `Submitter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Submitter` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Submitter') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Submitter',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Company` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Submitter`
--

LOCK TABLES `Submitter` WRITE;
/*!40000 ALTER TABLE `Submitter` DISABLE KEYS */;
/*!40000 ALTER TABLE `Submitter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit`
--

DROP TABLE IF EXISTS `Summit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Summit') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Summit',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitBeginDate` datetime DEFAULT NULL,
  `SummitEndDate` datetime DEFAULT NULL,
  `RegistrationBeginDate` datetime DEFAULT NULL,
  `RegistrationEndDate` datetime DEFAULT NULL,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  `DateLabel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Link` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Slug` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RegistrationLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ComingSoonBtnText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SecondaryRegistrationLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SecondaryRegistrationBtnText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExternalEventId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TimeZoneIdentifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `StartShowingVenuesDate` datetime DEFAULT NULL,
  `MaxSubmissionAllowedPerUser` int NOT NULL DEFAULT '0',
  `ScheduleDefaultStartDate` datetime DEFAULT NULL,
  `AvailableOnApi` tinyint unsigned NOT NULL DEFAULT '0',
  `CalendarSyncName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CalendarSyncDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MeetingRoomBookingStartTime` time DEFAULT NULL,
  `MeetingRoomBookingEndTime` time DEFAULT NULL,
  `MeetingRoomBookingSlotLength` int NOT NULL DEFAULT '0',
  `MeetingRoomBookingMaxAllowed` int NOT NULL DEFAULT '0',
  `ApiFeedType` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ApiFeedUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ApiFeedKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LogoID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  `ReAssignTicketTillDate` datetime DEFAULT NULL,
  `RegistrationDisclaimerContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegistrationDisclaimerMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ExternalRegistrationFeedType` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExternalRegistrationFeedApiKey` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BeginAllowBookingDate` datetime DEFAULT NULL,
  `EndAllowBookingDate` datetime DEFAULT NULL,
  `RegistrationReminderEmailsDaysInterval` int DEFAULT NULL,
  `RegistrationSlugPrefix` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ScheduleDefaultPageUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleDefaultEventDetailUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleOGSiteName` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleOGImageUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleOGImageSecureUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleOGImageWidth` int NOT NULL DEFAULT '0',
  `ScheduleOGImageHeight` int NOT NULL DEFAULT '0',
  `ScheduleFacebookAppId` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleIOSAppName` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleIOSAppStoreId` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleIOSAppCustomSchema` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleAndroidAppName` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleAndroidAppPackage` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleAndroidAppCustomSchema` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleTwitterAppName` longtext COLLATE utf8mb4_0900_as_cs,
  `ScheduleTwitterText` longtext COLLATE utf8mb4_0900_as_cs,
  `DefaultPageUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `SpeakerConfirmationDefaultPageUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `VirtualSiteUrl` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MarketingSiteUrl` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MarketingSiteOAuth2ClientId` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VirtualSiteOAuth2ClientId` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SupportEmail` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RegistrationSendQRAsImageAttachmentOnTicketEmail` tinyint(1) DEFAULT '0',
  `RegistrationSendTicketAsPDFAttachmentOnTicketEmail` tinyint(1) DEFAULT '0',
  `RegistrationSendTicketEmailAutomatically` tinyint(1) DEFAULT '1',
  `RegistrationAllowUpdateAttendeeExtraQuestions` tinyint(1) DEFAULT '0',
  `TimeZoneLabel` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RegistrationAllowAutomaticReminderEmails` tinyint(1) NOT NULL DEFAULT '1',
  `RegistrationSendOrderEmailAutomatically` tinyint(1) DEFAULT '1',
  `ExternalRegistrationFeedLastIngestDate` datetime DEFAULT NULL,
  `RegistrationAllowedRefundRequestTillDate` datetime DEFAULT NULL,
  `MarketingSiteOAuth2ClientScopes` longtext COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Summit_RegistrationSlugPrefix` (`RegistrationSlugPrefix`),
  KEY `LogoID` (`LogoID`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit`
--

LOCK TABLES `Summit` WRITE;
/*!40000 ALTER TABLE `Summit` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAbstractLocation`
--

DROP TABLE IF EXISTS `SummitAbstractLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAbstractLocation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAbstractLocation','SummitGeoLocatedLocation','SummitExternalLocation','SummitAirport','SummitHotel','SummitVenue','SummitVenueRoom','SummitBookableVenueRoom') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAbstractLocation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '1',
  `LocationType` enum('External','Internal','None') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `SummitID` int DEFAULT NULL,
  `ShortName` varchar(255) COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=422 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAbstractLocation`
--

LOCK TABLES `SummitAbstractLocation` WRITE;
/*!40000 ALTER TABLE `SummitAbstractLocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAbstractLocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAccessLevelType`
--

DROP TABLE IF EXISTS `SummitAccessLevelType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAccessLevelType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAccessLevelType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAccessLevelType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  `TemplateContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAccessLevelType`
--

LOCK TABLES `SummitAccessLevelType` WRITE;
/*!40000 ALTER TABLE `SummitAccessLevelType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAccessLevelType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitActivityDate`
--

DROP TABLE IF EXISTS `SummitActivityDate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitActivityDate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitActivityDate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitActivityDate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitUpdatesPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitUpdatesPageID` (`SummitUpdatesPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitActivityDate`
--

LOCK TABLES `SummitActivityDate` WRITE;
/*!40000 ALTER TABLE `SummitActivityDate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitActivityDate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAddOn`
--

DROP TABLE IF EXISTS `SummitAddOn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAddOn` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAddOn') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAddOn',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Cost` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MaxAvailable` int NOT NULL DEFAULT '0',
  `CurrentlyAvailable` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  `ShowQuantity` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAddOn`
--

LOCK TABLES `SummitAddOn` WRITE;
/*!40000 ALTER TABLE `SummitAddOn` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAddOn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAdministratorPermissionGroup`
--

DROP TABLE IF EXISTS `SummitAdministratorPermissionGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAdministratorPermissionGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime DEFAULT NULL,
  `LastEdited` datetime DEFAULT NULL,
  `ClassName` enum('SummitAdministratorPermissionGroup') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAdministratorPermissionGroup',
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_1D5C1CCDEAF7576F` (`Title`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAdministratorPermissionGroup`
--

LOCK TABLES `SummitAdministratorPermissionGroup` WRITE;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAdministratorPermissionGroup_Members`
--

DROP TABLE IF EXISTS `SummitAdministratorPermissionGroup_Members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAdministratorPermissionGroup_Members` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MemberID` int NOT NULL DEFAULT '0',
  `SummitAdministratorPermissionGroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_5CB435FD7B868B2A522B9974` (`SummitAdministratorPermissionGroupID`,`MemberID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitAdministratorPermissionGroupID` (`SummitAdministratorPermissionGroupID`)
) ENGINE=InnoDB AUTO_INCREMENT=2182 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAdministratorPermissionGroup_Members`
--

LOCK TABLES `SummitAdministratorPermissionGroup_Members` WRITE;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup_Members` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup_Members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAdministratorPermissionGroup_Summits`
--

DROP TABLE IF EXISTS `SummitAdministratorPermissionGroup_Summits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAdministratorPermissionGroup_Summits` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `SummitAdministratorPermissionGroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_6FA09E417B868B2A90CF7278` (`SummitAdministratorPermissionGroupID`,`SummitID`),
  KEY `SummitID` (`SummitID`),
  KEY `SummitAdministratorPermissionGroupID` (`SummitAdministratorPermissionGroupID`)
) ENGINE=InnoDB AUTO_INCREMENT=218 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAdministratorPermissionGroup_Summits`
--

LOCK TABLES `SummitAdministratorPermissionGroup_Summits` WRITE;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup_Summits` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAdministratorPermissionGroup_Summits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAirport`
--

DROP TABLE IF EXISTS `SummitAirport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAirport` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Type` enum('International','Domestic') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'International',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAirport`
--

LOCK TABLES `SummitAirport` WRITE;
/*!40000 ALTER TABLE `SummitAirport` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAirport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAppSchedPage`
--

DROP TABLE IF EXISTS `SummitAppSchedPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAppSchedPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAppSchedPage`
--

LOCK TABLES `SummitAppSchedPage` WRITE;
/*!40000 ALTER TABLE `SummitAppSchedPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAppSchedPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAppSchedPage_Live`
--

DROP TABLE IF EXISTS `SummitAppSchedPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAppSchedPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAppSchedPage_Live`
--

LOCK TABLES `SummitAppSchedPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitAppSchedPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAppSchedPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAppSchedPage_versions`
--

DROP TABLE IF EXISTS `SummitAppSchedPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAppSchedPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `EnableMobileSupport` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAppSchedPage_versions`
--

LOCK TABLES `SummitAppSchedPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitAppSchedPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAppSchedPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendee`
--

DROP TABLE IF EXISTS `SummitAttendee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendee` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAttendee') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAttendee',
  `LastEdited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `Created` datetime DEFAULT NULL,
  `SharedContactInfo` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitHallCheckedIn` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitHallCheckedInDate` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `FirstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `DisclaimerAcceptedDate` datetime DEFAULT NULL,
  `Company` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  `Status` enum('Incomplete','Complete') COLLATE utf8mb4_0900_as_cs DEFAULT 'Incomplete',
  `LastReminderEmailSentDate` datetime DEFAULT NULL,
  `AdminNotes` varchar(1024) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitVirtualCheckedInDate` datetime DEFAULT NULL,
  `InvitationEmailSentDate` datetime DEFAULT NULL,
  `PublicEditionEmailSentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitAttendee_Email_SummitID` (`SummitID`,`Email`),
  UNIQUE KEY `SummitAttendee_Member_Summit` (`MemberID`,`SummitID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `FK_SummitAttendee_CompanyID` (`CompanyID`),
  CONSTRAINT `FK_SummitAttendee_CompanyID` FOREIGN KEY (`CompanyID`) REFERENCES `Company` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendee_MemberID` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendee_SummitID` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=27970 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendee`
--

LOCK TABLES `SummitAttendee` WRITE;
/*!40000 ALTER TABLE `SummitAttendee` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendee` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeBadge`
--

DROP TABLE IF EXISTS `SummitAttendeeBadge`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeBadge` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAttendeeBadge') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAttendeeBadge',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `IsVoid` tinyint unsigned NOT NULL DEFAULT '0',
  `QRCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TicketID` int DEFAULT NULL,
  `BadgeTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TicketID` (`TicketID`),
  KEY `BadgeTypeID` (`BadgeTypeID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_BadgeTypeID` FOREIGN KEY (`BadgeTypeID`) REFERENCES `SummitBadgeType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32773 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeBadge`
--

LOCK TABLES `SummitAttendeeBadge` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeBadge` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeBadge` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeBadgePrint`
--

DROP TABLE IF EXISTS `SummitAttendeeBadgePrint`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeBadgePrint` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime DEFAULT NULL,
  `LastEdited` datetime DEFAULT NULL,
  `PrintDate` datetime DEFAULT NULL,
  `BadgeID` int DEFAULT NULL,
  `RequestorID` int DEFAULT NULL,
  `ClassName` enum('SummitAttendeeBadgePrint') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAttendeeBadgePrint',
  `SummitBadgeViewTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BadgeID` (`BadgeID`),
  KEY `RequestorID` (`RequestorID`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitBadgeViewTypeID` (`SummitBadgeViewTypeID`),
  CONSTRAINT `FK_A3FFCDAE43A322D3` FOREIGN KEY (`RequestorID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_A3FFCDAE590501E8` FOREIGN KEY (`BadgeID`) REFERENCES `SummitAttendeeBadge` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitAttendeeBadgePrint_SummitBadgeViewType` FOREIGN KEY (`SummitBadgeViewTypeID`) REFERENCES `SummitBadgeViewType` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12857 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeBadgePrint`
--

LOCK TABLES `SummitAttendeeBadgePrint` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeBadgePrint` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeBadgePrint` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeBadgePrintRule`
--

DROP TABLE IF EXISTS `SummitAttendeeBadgePrintRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeBadgePrintRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime DEFAULT NULL,
  `LastEdited` datetime DEFAULT NULL,
  `MaxPrintTimes` int NOT NULL DEFAULT '0',
  `GroupID` int DEFAULT NULL,
  `ClassName` enum('SummitAttendeeBadgePrintRule') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAttendeeBadgePrintRule',
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_ED267F7195291E4` FOREIGN KEY (`GroupID`) REFERENCES `Group` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=315 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeBadgePrintRule`
--

LOCK TABLES `SummitAttendeeBadgePrintRule` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeBadgePrintRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeBadgePrintRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeBadge_Features`
--

DROP TABLE IF EXISTS `SummitAttendeeBadge_Features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeBadge_Features` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitAttendeeBadgeID` int NOT NULL DEFAULT '0',
  `SummitBadgeFeatureTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitAttendeeBadge_Features_Unique` (`SummitAttendeeBadgeID`,`SummitBadgeFeatureTypeID`),
  KEY `SummitAttendeeBadgeID` (`SummitAttendeeBadgeID`),
  KEY `SummitBadgeFeatureTypeID` (`SummitBadgeFeatureTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=7099 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeBadge_Features`
--

LOCK TABLES `SummitAttendeeBadge_Features` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeBadge_Features` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeBadge_Features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeTicket`
--

DROP TABLE IF EXISTS `SummitAttendeeTicket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeTicket` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitAttendeeTicket') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitAttendeeTicket',
  `LastEdited` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `Created` datetime DEFAULT NULL,
  `ExternalOrderId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalAttendeeId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TicketBoughtDate` datetime DEFAULT NULL,
  `TicketChangedDate` datetime DEFAULT NULL,
  `TicketTypeID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  `Status` enum('Reserved','Cancelled','RefundRequested','Refunded','Confirmed','Paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Reserved',
  `Number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `RawCost` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `Discount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `RefundedAmount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `QRCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HashCreationDate` datetime DEFAULT NULL,
  `SummitAttendeeBadgeID` int DEFAULT NULL,
  `OrderID` int DEFAULT NULL,
  `PromoCodeID` int DEFAULT NULL,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  KEY `TicketTypeID` (`TicketTypeID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitAttendeeBadgeID` (`SummitAttendeeBadgeID`),
  KEY `OrderID` (`OrderID`),
  KEY `PromoCodeID` (`PromoCodeID`),
  KEY `Order_Attendee` (`ExternalOrderId`,`ExternalAttendeeId`),
  CONSTRAINT `FK_SummitAttendeeTicket_Badge` FOREIGN KEY (`SummitAttendeeBadgeID`) REFERENCES `SummitAttendeeBadge` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendeeTicket_ORDER` FOREIGN KEY (`OrderID`) REFERENCES `SummitOrder` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendeeTicket_Owner` FOREIGN KEY (`OwnerID`) REFERENCES `SummitAttendee` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendeeTicket_PromoCode` FOREIGN KEY (`PromoCodeID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitAttendeeTicket_Type` FOREIGN KEY (`TicketTypeID`) REFERENCES `SummitTicketType` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=32765 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeTicket`
--

LOCK TABLES `SummitAttendeeTicket` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeTicket` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeTicket` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeTicketFormerHash`
--

DROP TABLE IF EXISTS `SummitAttendeeTicketFormerHash`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeTicketFormerHash` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitAttendeeTicketID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Hash` (`Hash`),
  KEY `SummitAttendeeTicketID` (`SummitAttendeeTicketID`),
  CONSTRAINT `FK_75D2F561D637E86A` FOREIGN KEY (`SummitAttendeeTicketID`) REFERENCES `SummitAttendeeTicket` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91087 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeTicketFormerHash`
--

LOCK TABLES `SummitAttendeeTicketFormerHash` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeTicketFormerHash` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeTicketFormerHash` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeTicketRefundRequest`
--

DROP TABLE IF EXISTS `SummitAttendeeTicketRefundRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeTicketRefundRequest` (
  `ID` int NOT NULL,
  `TicketID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TicketID` (`TicketID`),
  CONSTRAINT `FK_A6F6E11611D3633A` FOREIGN KEY (`ID`) REFERENCES `SummitRefundRequest` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitAttendeeTicketRefundRequest_SummitRefundRequest` FOREIGN KEY (`TicketID`) REFERENCES `SummitAttendeeTicket` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeTicketRefundRequest`
--

LOCK TABLES `SummitAttendeeTicketRefundRequest` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeTicketRefundRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeTicketRefundRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAttendeeTicket_Taxes`
--

DROP TABLE IF EXISTS `SummitAttendeeTicket_Taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAttendeeTicket_Taxes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitAttendeeTicketID` int NOT NULL DEFAULT '0',
  `SummitTaxTypeID` int NOT NULL DEFAULT '0',
  `Amount` decimal(32,10) NOT NULL DEFAULT '0.0000000000',
  PRIMARY KEY (`ID`),
  KEY `SummitAttendeeTicketID` (`SummitAttendeeTicketID`),
  KEY `SummitTaxTypeID` (`SummitTaxTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAttendeeTicket_Taxes`
--

LOCK TABLES `SummitAttendeeTicket_Taxes` WRITE;
/*!40000 ALTER TABLE `SummitAttendeeTicket_Taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAttendeeTicket_Taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitAuditLog`
--

DROP TABLE IF EXISTS `SummitAuditLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitAuditLog` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_SummitAuditLog_AuditLog` FOREIGN KEY (`ID`) REFERENCES `AuditLog` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2417 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitAuditLog`
--

LOCK TABLES `SummitAuditLog` WRITE;
/*!40000 ALTER TABLE `SummitAuditLog` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitAuditLog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeFeatureType`
--

DROP TABLE IF EXISTS `SummitBadgeFeatureType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeFeatureType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitBadgeFeatureType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitBadgeFeatureType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TemplateContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `ImageID` (`ImageID`),
  CONSTRAINT `FK_506A5DAFE4201A19` FOREIGN KEY (`ImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeFeatureType`
--

LOCK TABLES `SummitBadgeFeatureType` WRITE;
/*!40000 ALTER TABLE `SummitBadgeFeatureType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeFeatureType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeType`
--

DROP TABLE IF EXISTS `SummitBadgeType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitBadgeType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitBadgeType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  `TemplateContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `FileID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `FileID` (`FileID`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeType`
--

LOCK TABLES `SummitBadgeType` WRITE;
/*!40000 ALTER TABLE `SummitBadgeType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeType_AccessLevels`
--

DROP TABLE IF EXISTS `SummitBadgeType_AccessLevels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeType_AccessLevels` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitBadgeTypeID` int NOT NULL DEFAULT '0',
  `SummitAccessLevelTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitBadgeTypeID` (`SummitBadgeTypeID`),
  KEY `SummitAccessLevelTypeID` (`SummitAccessLevelTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=260 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeType_AccessLevels`
--

LOCK TABLES `SummitBadgeType_AccessLevels` WRITE;
/*!40000 ALTER TABLE `SummitBadgeType_AccessLevels` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeType_AccessLevels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeType_BadgeFeatures`
--

DROP TABLE IF EXISTS `SummitBadgeType_BadgeFeatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeType_BadgeFeatures` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitBadgeTypeID` int NOT NULL DEFAULT '0',
  `SummitBadgeFeatureTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitBadgeTypeID` (`SummitBadgeTypeID`),
  KEY `SummitBadgeFeatureTypeID` (`SummitBadgeFeatureTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeType_BadgeFeatures`
--

LOCK TABLES `SummitBadgeType_BadgeFeatures` WRITE;
/*!40000 ALTER TABLE `SummitBadgeType_BadgeFeatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeType_BadgeFeatures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeViewType`
--

DROP TABLE IF EXISTS `SummitBadgeViewType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeViewType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitBadgeViewType',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsDefault` tinyint(1) NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitID_Name` (`SummitID`,`Name`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_SummitBadgeViewType_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeViewType`
--

LOCK TABLES `SummitBadgeViewType` WRITE;
/*!40000 ALTER TABLE `SummitBadgeViewType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeViewType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBadgeViewType_SummitBadgeType`
--

DROP TABLE IF EXISTS `SummitBadgeViewType_SummitBadgeType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBadgeViewType_SummitBadgeType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitBadgeViewType',
  `SummitBadgeViewTypeID` int DEFAULT NULL,
  `SummitBadgeTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_SummitBadgeViewTypeID_SummitBadgeTypeID` (`SummitBadgeViewTypeID`,`SummitBadgeTypeID`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitBadgeViewTypeID` (`SummitBadgeViewTypeID`),
  KEY `SummitBadgeTypeID` (`SummitBadgeTypeID`),
  CONSTRAINT `FK_SummitBadgeViewType_SummitBadgeType_SummitBadgeType` FOREIGN KEY (`SummitBadgeTypeID`) REFERENCES `SummitBadgeType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitBadgeViewType_SummitBadgeType_SummitBadgeViewType` FOREIGN KEY (`SummitBadgeViewTypeID`) REFERENCES `SummitBadgeViewType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBadgeViewType_SummitBadgeType`
--

LOCK TABLES `SummitBadgeViewType_SummitBadgeType` WRITE;
/*!40000 ALTER TABLE `SummitBadgeViewType_SummitBadgeType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBadgeViewType_SummitBadgeType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBanner`
--

DROP TABLE IF EXISTS `SummitBanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBanner` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitBanner') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitBanner',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MainText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MainTextColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SeparatorColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BackgroundColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ButtonLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ButtonTextColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SmallText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SmallTextColor` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Template` enum('HighlightBar','Editorial') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'HighlightBar',
  `Enabled` tinyint unsigned NOT NULL DEFAULT '1',
  `LogoID` int DEFAULT NULL,
  `PictureID` int DEFAULT NULL,
  `ParentPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LogoID` (`LogoID`),
  KEY `PictureID` (`PictureID`),
  KEY `ParentPageID` (`ParentPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBanner`
--

LOCK TABLES `SummitBanner` WRITE;
/*!40000 ALTER TABLE `SummitBanner` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBanner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBookableVenueRoom`
--

DROP TABLE IF EXISTS `SummitBookableVenueRoom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBookableVenueRoom` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TimeSlotCost` int NOT NULL DEFAULT '0',
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBookableVenueRoom`
--

LOCK TABLES `SummitBookableVenueRoom` WRITE;
/*!40000 ALTER TABLE `SummitBookableVenueRoom` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBookableVenueRoom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBookableVenueRoomAttributeType`
--

DROP TABLE IF EXISTS `SummitBookableVenueRoomAttributeType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBookableVenueRoomAttributeType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitBookableVenueRoomAttributeType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitBookableVenueRoomAttributeType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitID_Type` (`SummitID`,`Type`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBookableVenueRoomAttributeType`
--

LOCK TABLES `SummitBookableVenueRoomAttributeType` WRITE;
/*!40000 ALTER TABLE `SummitBookableVenueRoomAttributeType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBookableVenueRoomAttributeType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBookableVenueRoomAttributeValue`
--

DROP TABLE IF EXISTS `SummitBookableVenueRoomAttributeValue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBookableVenueRoomAttributeValue` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitBookableVenueRoomAttributeValue') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitBookableVenueRoomAttributeValue',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `TypeID_Value` (`TypeID`,`Value`),
  KEY `TypeID` (`TypeID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBookableVenueRoomAttributeValue`
--

LOCK TABLES `SummitBookableVenueRoomAttributeValue` WRITE;
/*!40000 ALTER TABLE `SummitBookableVenueRoomAttributeValue` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBookableVenueRoomAttributeValue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitBookableVenueRoom_Attributes`
--

DROP TABLE IF EXISTS `SummitBookableVenueRoom_Attributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitBookableVenueRoom_Attributes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitBookableVenueRoomID` int NOT NULL DEFAULT '0',
  `SummitBookableVenueRoomAttributeValueID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitBookableVenueRoomID` (`SummitBookableVenueRoomID`),
  KEY `SummitBookableVenueRoomAttributeValueID` (`SummitBookableVenueRoomAttributeValueID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitBookableVenueRoom_Attributes`
--

LOCK TABLES `SummitBookableVenueRoom_Attributes` WRITE;
/*!40000 ALTER TABLE `SummitBookableVenueRoom_Attributes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitBookableVenueRoom_Attributes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitCategoriesPage`
--

DROP TABLE IF EXISTS `SummitCategoriesPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitCategoriesPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitCategoriesPage`
--

LOCK TABLES `SummitCategoriesPage` WRITE;
/*!40000 ALTER TABLE `SummitCategoriesPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitCategoriesPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitCategoriesPage_Live`
--

DROP TABLE IF EXISTS `SummitCategoriesPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitCategoriesPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitCategoriesPage_Live`
--

LOCK TABLES `SummitCategoriesPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitCategoriesPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitCategoriesPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitCategoriesPage_versions`
--

DROP TABLE IF EXISTS `SummitCategoriesPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitCategoriesPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `HeaderTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitCategoriesPage_versions`
--

LOCK TABLES `SummitCategoriesPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitCategoriesPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitCategoriesPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitCategoryChange`
--

DROP TABLE IF EXISTS `SummitCategoryChange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitCategoryChange` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitCategoryChange') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitCategoryChange',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Comment` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ApprovalDate` datetime DEFAULT NULL,
  `Status` int NOT NULL DEFAULT '0',
  `Reason` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NewCategoryID` int DEFAULT NULL,
  `OldCategoryID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  `ReqesterID` int DEFAULT NULL,
  `OldCatApproverID` int DEFAULT NULL,
  `NewCatApproverID` int DEFAULT NULL,
  `AdminApproverID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `NewCategoryID` (`NewCategoryID`),
  KEY `OldCategoryID` (`OldCategoryID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `ReqesterID` (`ReqesterID`),
  KEY `OldCatApproverID` (`OldCatApproverID`),
  KEY `NewCatApproverID` (`NewCatApproverID`),
  KEY `AdminApproverID` (`AdminApproverID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitCategoryChange`
--

LOCK TABLES `SummitCategoryChange` WRITE;
/*!40000 ALTER TABLE `SummitCategoryChange` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitCategoryChange` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitDocument`
--

DROP TABLE IF EXISTS `SummitDocument`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitDocument` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `FileID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `ShowAlways` tinyint(1) NOT NULL DEFAULT '0',
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `FileID` (`FileID`),
  KEY `SummitID` (`SummitID`),
  KEY `IDX_SummitDocument_SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_C43764E590CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_C43764E593076D5B` FOREIGN KEY (`FileID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitDocument_SelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitDocument`
--

LOCK TABLES `SummitDocument` WRITE;
/*!40000 ALTER TABLE `SummitDocument` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitDocument` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitDocument_EventTypes`
--

DROP TABLE IF EXISTS `SummitDocument_EventTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitDocument_EventTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitDocumentID` int DEFAULT NULL,
  `SummitEventTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_CCDB2615780505E5DF6E48FA` (`SummitDocumentID`,`SummitEventTypeID`),
  KEY `SummitDocumentID` (`SummitDocumentID`),
  KEY `SummitEventTypeID` (`SummitEventTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitDocument_EventTypes`
--

LOCK TABLES `SummitDocument_EventTypes` WRITE;
/*!40000 ALTER TABLE `SummitDocument_EventTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitDocument_EventTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEmailEventFlow`
--

DROP TABLE IF EXISTS `SummitEmailEventFlow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEmailEventFlow` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitEmailEventFlow') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEmailEventFlow',
  `EmailTemplateIdentifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SummitEmailEventFlowTypeID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEmailEventFlowTypeID` (`SummitEmailEventFlowTypeID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_3BF9423B38E81E75` FOREIGN KEY (`SummitEmailEventFlowTypeID`) REFERENCES `SummitEmailEventFlowType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_3BF9423B90CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14915 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEmailEventFlow`
--

LOCK TABLES `SummitEmailEventFlow` WRITE;
/*!40000 ALTER TABLE `SummitEmailEventFlow` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEmailEventFlow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEmailEventFlowType`
--

DROP TABLE IF EXISTS `SummitEmailEventFlowType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEmailEventFlowType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitEmailEventFlowType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEmailEventFlowType',
  `Slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `DefaultEmailTemplateIdentifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SummitEmailFlowTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEmailFlowTypeID` (`SummitEmailFlowTypeID`),
  CONSTRAINT `FK_CAD6DC9D19C90B6` FOREIGN KEY (`SummitEmailFlowTypeID`) REFERENCES `SummitEmailFlowType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=827 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEmailEventFlowType`
--

LOCK TABLES `SummitEmailEventFlowType` WRITE;
/*!40000 ALTER TABLE `SummitEmailEventFlowType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEmailEventFlowType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEmailFlowType`
--

DROP TABLE IF EXISTS `SummitEmailFlowType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEmailFlowType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitEmailFlowType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEmailFlowType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=95 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEmailFlowType`
--

LOCK TABLES `SummitEmailFlowType` WRITE;
/*!40000 ALTER TABLE `SummitEmailFlowType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEmailFlowType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEntityEvent`
--

DROP TABLE IF EXISTS `SummitEntityEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEntityEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitEntityEvent') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEntityEvent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `EntityID` int NOT NULL DEFAULT '0',
  `EntityClassName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` enum('UPDATE','INSERT','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'UPDATE',
  `Metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=1698152 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEntityEvent`
--

LOCK TABLES `SummitEntityEvent` WRITE;
/*!40000 ALTER TABLE `SummitEntityEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEntityEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEvent`
--

DROP TABLE IF EXISTS `SummitEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitEvent','SummitEventWithFile','SummitGroupEvent','Presentation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEvent',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Abstract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SocialSummary` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `Published` tinyint unsigned NOT NULL DEFAULT '0',
  `PublishedDate` datetime DEFAULT NULL,
  `AllowFeedBack` tinyint unsigned NOT NULL DEFAULT '0',
  `AvgFeedbackRate` float NOT NULL DEFAULT '0',
  `HeadCount` int NOT NULL DEFAULT '0',
  `RSVPLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RSVPMaxUserNumber` int NOT NULL DEFAULT '0',
  `RSVPMaxUserWaitListNumber` int NOT NULL DEFAULT '0',
  `Occupancy` enum('EMPTY','25%','50%','75%','FULL') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EMPTY',
  `ExternalId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  `RSVPTemplateID` int DEFAULT NULL,
  `CategoryID` int DEFAULT NULL,
  `StreamingUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `EtherpadLink` longtext COLLATE utf8mb4_0900_as_cs,
  `MeetingUrl` longtext COLLATE utf8mb4_0900_as_cs,
  `ImageID` int DEFAULT NULL,
  `MuxPlaybackID` longtext COLLATE utf8mb4_0900_as_cs,
  `MuxAssetID` longtext COLLATE utf8mb4_0900_as_cs,
  `Level` enum('Beginner','Intermediate','Advanced','N/A') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Beginner',
  `CreatedByID` int DEFAULT NULL,
  `UpdatedByID` int DEFAULT NULL,
  `StreamingType` varchar(4) COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'LIVE',
  `ShowSponsors` tinyint(1) NOT NULL DEFAULT '0',
  `DurationInSeconds` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `LocationID` (`LocationID`),
  KEY `SummitID` (`SummitID`),
  KEY `TypeID` (`TypeID`),
  KEY `RSVPTemplateID` (`RSVPTemplateID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `ClassName` (`ClassName`),
  KEY `ImageID` (`ImageID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `UpdatedByID` (`UpdatedByID`),
  CONSTRAINT `FK_Summit_event_RSVPTemplate` FOREIGN KEY (`RSVPTemplateID`) REFERENCES `RSVPTemplate` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_Summit_Event_UpdatedBy` FOREIGN KEY (`UpdatedByID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitEvent_CreatedBy` FOREIGN KEY (`CreatedByID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitEvent_Image` FOREIGN KEY (`ImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitEvent_PresentationCategory` FOREIGN KEY (`CategoryID`) REFERENCES `PresentationCategory` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `FK_SummitEvent_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `FK_SummitEvent_SummitAbstractLocation` FOREIGN KEY (`LocationID`) REFERENCES `SummitAbstractLocation` (`ID`) ON DELETE SET NULL,
  CONSTRAINT `FK_SummitEvent_SummitEventType` FOREIGN KEY (`TypeID`) REFERENCES `SummitEventType` (`ID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3615 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEvent`
--

LOCK TABLES `SummitEvent` WRITE;
/*!40000 ALTER TABLE `SummitEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEventAttendanceMetric`
--

DROP TABLE IF EXISTS `SummitEventAttendanceMetric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEventAttendanceMetric` (
  `ID` int NOT NULL,
  `ClassName` enum('SummitEventAttendanceMetric') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEventAttendanceMetric',
  `SummitEventID` int DEFAULT NULL,
  `SubType` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'VIRTUAL',
  `SummitVenueRoomID` int DEFAULT NULL,
  `SummitAttendeeID` int DEFAULT NULL,
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `SummitVenueRoomID` (`SummitVenueRoomID`),
  KEY `SummitAttendeeID` (`SummitAttendeeID`),
  KEY `CreatedByID` (`CreatedByID`),
  CONSTRAINT `FK_967BCC3722CF6AF5` FOREIGN KEY (`SummitEventID`) REFERENCES `SummitEvent` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitEventAttendanceMetric_CreatedBy` FOREIGN KEY (`CreatedByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitEventAttendanceMetric_SummitAttendee` FOREIGN KEY (`SummitAttendeeID`) REFERENCES `SummitAttendee` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitEventAttendanceMetric_SummitMetric` FOREIGN KEY (`ID`) REFERENCES `SummitMetric` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitEventAttendanceMetric_SummitVenueRoom` FOREIGN KEY (`SummitVenueRoomID`) REFERENCES `SummitVenueRoom` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEventAttendanceMetric`
--

LOCK TABLES `SummitEventAttendanceMetric` WRITE;
/*!40000 ALTER TABLE `SummitEventAttendanceMetric` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEventAttendanceMetric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEventAuditLog`
--

DROP TABLE IF EXISTS `SummitEventAuditLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEventAuditLog` (
  `ID` int NOT NULL,
  `EventID` int NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `EventID` (`EventID`),
  CONSTRAINT `FK_SummitEventAuditLog_AuditLog` FOREIGN KEY (`ID`) REFERENCES `AuditLog` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEventAuditLog`
--

LOCK TABLES `SummitEventAuditLog` WRITE;
/*!40000 ALTER TABLE `SummitEventAuditLog` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEventAuditLog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEventFeedback`
--

DROP TABLE IF EXISTS `SummitEventFeedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEventFeedback` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitEventFeedback') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEventFeedback',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Rate` float NOT NULL DEFAULT '0',
  `Note` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `ApprovedDate` datetime DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  `ApprovedByID` int DEFAULT NULL,
  `EventID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ApprovedByID` (`ApprovedByID`),
  KEY `EventID` (`EventID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=338 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEventFeedback`
--

LOCK TABLES `SummitEventFeedback` WRITE;
/*!40000 ALTER TABLE `SummitEventFeedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEventFeedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEventType`
--

DROP TABLE IF EXISTS `SummitEventType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEventType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitEventType','PresentationType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitEventType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Color` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `BlackoutTimes` tinyint unsigned NOT NULL DEFAULT '0',
  `UseSponsors` tinyint unsigned NOT NULL DEFAULT '0',
  `AreSponsorsMandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `AllowsAttachment` tinyint unsigned NOT NULL DEFAULT '0',
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  `IsPrivate` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `AllowsLevel` tinyint(1) NOT NULL DEFAULT '0',
  `AllowsPublishingDates` tinyint(1) NOT NULL DEFAULT '1',
  `AllowsLocation` tinyint(1) NOT NULL DEFAULT '1',
  `AllowsLocationAndTimeFrameCollision` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_SummitEventType_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=635 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEventType`
--

LOCK TABLES `SummitEventType` WRITE;
/*!40000 ALTER TABLE `SummitEventType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEventType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEventWithFile`
--

DROP TABLE IF EXISTS `SummitEventWithFile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEventWithFile` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AttachmentID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `AttachmentID` (`AttachmentID`)
) ENGINE=InnoDB AUTO_INCREMENT=3593 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEventWithFile`
--

LOCK TABLES `SummitEventWithFile` WRITE;
/*!40000 ALTER TABLE `SummitEventWithFile` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEventWithFile` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEvent_Sponsors`
--

DROP TABLE IF EXISTS `SummitEvent_Sponsors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEvent_Sponsors` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitEventID` int NOT NULL DEFAULT '0',
  `CompanyID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `CompanyID` (`CompanyID`)
) ENGINE=InnoDB AUTO_INCREMENT=3768 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEvent_Sponsors`
--

LOCK TABLES `SummitEvent_Sponsors` WRITE;
/*!40000 ALTER TABLE `SummitEvent_Sponsors` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEvent_Sponsors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitEvent_Tags`
--

DROP TABLE IF EXISTS `SummitEvent_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitEvent_Tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitEventID` int NOT NULL DEFAULT '0',
  `TagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_SummitEvent_Tags_SummitEvent` FOREIGN KEY (`SummitEventID`) REFERENCES `SummitEvent` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitEvent_Tags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=49175 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitEvent_Tags`
--

LOCK TABLES `SummitEvent_Tags` WRITE;
/*!40000 ALTER TABLE `SummitEvent_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitEvent_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitExternalLocation`
--

DROP TABLE IF EXISTS `SummitExternalLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitExternalLocation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Capacity` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=312 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitExternalLocation`
--

LOCK TABLES `SummitExternalLocation` WRITE;
/*!40000 ALTER TABLE `SummitExternalLocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitExternalLocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitFutureLanding`
--

DROP TABLE IF EXISTS `SummitFutureLanding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitFutureLanding` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BGImageOffset` int NOT NULL DEFAULT '0',
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MainTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocSubtitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProspectusUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShareText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitFutureLanding`
--

LOCK TABLES `SummitFutureLanding` WRITE;
/*!40000 ALTER TABLE `SummitFutureLanding` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitFutureLanding` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitFutureLanding_Live`
--

DROP TABLE IF EXISTS `SummitFutureLanding_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitFutureLanding_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BGImageOffset` int NOT NULL DEFAULT '0',
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MainTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocSubtitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProspectusUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShareText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitFutureLanding_Live`
--

LOCK TABLES `SummitFutureLanding_Live` WRITE;
/*!40000 ALTER TABLE `SummitFutureLanding_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitFutureLanding_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitFutureLanding_versions`
--

DROP TABLE IF EXISTS `SummitFutureLanding_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitFutureLanding_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `BGImageOffset` int NOT NULL DEFAULT '0',
  `IntroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MainTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocSubtitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ProspectusUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RegisterUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShareText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `PhotoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitFutureLanding_versions`
--

LOCK TABLES `SummitFutureLanding_versions` WRITE;
/*!40000 ALTER TABLE `SummitFutureLanding_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitFutureLanding_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitGeoLocatedLocation`
--

DROP TABLE IF EXISTS `SummitGeoLocatedLocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitGeoLocatedLocation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Address1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Address2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ZipCode` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `State` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `WebSiteUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Lng` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Lat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `DetailsPage` tinyint unsigned NOT NULL DEFAULT '0',
  `LocationMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=414 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitGeoLocatedLocation`
--

LOCK TABLES `SummitGeoLocatedLocation` WRITE;
/*!40000 ALTER TABLE `SummitGeoLocatedLocation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitGeoLocatedLocation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitGroupEvent`
--

DROP TABLE IF EXISTS `SummitGroupEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitGroupEvent` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedByID` (`CreatedByID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitGroupEvent`
--

LOCK TABLES `SummitGroupEvent` WRITE;
/*!40000 ALTER TABLE `SummitGroupEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitGroupEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitGroupEvent_Groups`
--

DROP TABLE IF EXISTS `SummitGroupEvent_Groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitGroupEvent_Groups` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitGroupEventID` int NOT NULL DEFAULT '0',
  `GroupID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitGroupEventID` (`SummitGroupEventID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitGroupEvent_Groups`
--

LOCK TABLES `SummitGroupEvent_Groups` WRITE;
/*!40000 ALTER TABLE `SummitGroupEvent_Groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitGroupEvent_Groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHighlightPic`
--

DROP TABLE IF EXISTS `SummitHighlightPic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHighlightPic` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitHighlightPic') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitHighlightPic',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SummitHighlightsPageID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitHighlightsPageID` (`SummitHighlightsPageID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHighlightPic`
--

LOCK TABLES `SummitHighlightPic` WRITE;
/*!40000 ALTER TABLE `SummitHighlightPic` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHighlightPic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHighlightsPage`
--

DROP TABLE IF EXISTS `SummitHighlightsPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHighlightsPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextSummitText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttributionURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AttendanceQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompaniesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CountriesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SessionsQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentSummitFlickrUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedImageID` int DEFAULT NULL,
  `CurrentSummitBackgroundImageID` int DEFAULT NULL,
  `NextSummitTinyBackgroundImageID` int DEFAULT NULL,
  `NextSummitBackgroundImageID` int DEFAULT NULL,
  `StatisticsVideoPosterID` int DEFAULT NULL,
  `StatisticsVideoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReleaseAnnouncedImageID` (`ReleaseAnnouncedImageID`),
  KEY `CurrentSummitBackgroundImageID` (`CurrentSummitBackgroundImageID`),
  KEY `NextSummitTinyBackgroundImageID` (`NextSummitTinyBackgroundImageID`),
  KEY `NextSummitBackgroundImageID` (`NextSummitBackgroundImageID`),
  KEY `StatisticsVideoPosterID` (`StatisticsVideoPosterID`),
  KEY `StatisticsVideoID` (`StatisticsVideoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHighlightsPage`
--

LOCK TABLES `SummitHighlightsPage` WRITE;
/*!40000 ALTER TABLE `SummitHighlightsPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHighlightsPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHighlightsPage_Live`
--

DROP TABLE IF EXISTS `SummitHighlightsPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHighlightsPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextSummitText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttributionURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AttendanceQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompaniesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CountriesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SessionsQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentSummitFlickrUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedImageID` int DEFAULT NULL,
  `CurrentSummitBackgroundImageID` int DEFAULT NULL,
  `NextSummitTinyBackgroundImageID` int DEFAULT NULL,
  `NextSummitBackgroundImageID` int DEFAULT NULL,
  `StatisticsVideoPosterID` int DEFAULT NULL,
  `StatisticsVideoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReleaseAnnouncedImageID` (`ReleaseAnnouncedImageID`),
  KEY `CurrentSummitBackgroundImageID` (`CurrentSummitBackgroundImageID`),
  KEY `NextSummitTinyBackgroundImageID` (`NextSummitTinyBackgroundImageID`),
  KEY `NextSummitBackgroundImageID` (`NextSummitBackgroundImageID`),
  KEY `StatisticsVideoPosterID` (`StatisticsVideoPosterID`),
  KEY `StatisticsVideoID` (`StatisticsVideoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHighlightsPage_Live`
--

LOCK TABLES `SummitHighlightsPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitHighlightsPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHighlightsPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHighlightsPage_versions`
--

DROP TABLE IF EXISTS `SummitHighlightsPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHighlightsPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NextSummitText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SuccessAttributionURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AttendanceQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompaniesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CountriesRepresentedQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SessionsQty` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedButtonLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CurrentSummitFlickrUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl3` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StatisticsVideoUrl4` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReleaseAnnouncedImageID` int DEFAULT NULL,
  `CurrentSummitBackgroundImageID` int DEFAULT NULL,
  `NextSummitTinyBackgroundImageID` int DEFAULT NULL,
  `NextSummitBackgroundImageID` int DEFAULT NULL,
  `StatisticsVideoPosterID` int DEFAULT NULL,
  `StatisticsVideoID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `ReleaseAnnouncedImageID` (`ReleaseAnnouncedImageID`),
  KEY `CurrentSummitBackgroundImageID` (`CurrentSummitBackgroundImageID`),
  KEY `NextSummitTinyBackgroundImageID` (`NextSummitTinyBackgroundImageID`),
  KEY `NextSummitBackgroundImageID` (`NextSummitBackgroundImageID`),
  KEY `StatisticsVideoPosterID` (`StatisticsVideoPosterID`),
  KEY `StatisticsVideoID` (`StatisticsVideoID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHighlightsPage_versions`
--

LOCK TABLES `SummitHighlightsPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitHighlightsPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHighlightsPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHomePage`
--

DROP TABLE IF EXISTS `SummitHomePage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHomePage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IntroText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHomePage`
--

LOCK TABLES `SummitHomePage` WRITE;
/*!40000 ALTER TABLE `SummitHomePage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHomePage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHomePage_Live`
--

DROP TABLE IF EXISTS `SummitHomePage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHomePage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IntroText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHomePage_Live`
--

LOCK TABLES `SummitHomePage_Live` WRITE;
/*!40000 ALTER TABLE `SummitHomePage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHomePage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHomePage_versions`
--

DROP TABLE IF EXISTS `SummitHomePage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHomePage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `IntroText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHomePage_versions`
--

LOCK TABLES `SummitHomePage_versions` WRITE;
/*!40000 ALTER TABLE `SummitHomePage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHomePage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitHotel`
--

DROP TABLE IF EXISTS `SummitHotel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitHotel` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `BookingLink` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SoldOut` tinyint unsigned NOT NULL DEFAULT '0',
  `Type` enum('Primary','Alternate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Primary',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=257 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitHotel`
--

LOCK TABLES `SummitHotel` WRITE;
/*!40000 ALTER TABLE `SummitHotel` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitHotel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitImage`
--

DROP TABLE IF EXISTS `SummitImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitImage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitImage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitImage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Attribution` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OriginalURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitImage`
--

LOCK TABLES `SummitImage` WRITE;
/*!40000 ALTER TABLE `SummitImage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitImage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitKeynoteHighlight`
--

DROP TABLE IF EXISTS `SummitKeynoteHighlight`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitKeynoteHighlight` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitKeynoteHighlight') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitKeynoteHighlight',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Day` enum('Day1','Day2','Day3','Day4','Day5') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Day1',
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SummitHighlightsPageID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  `ThumbnailID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitHighlightsPageID` (`SummitHighlightsPageID`),
  KEY `ImageID` (`ImageID`),
  KEY `ThumbnailID` (`ThumbnailID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitKeynoteHighlight`
--

LOCK TABLES `SummitKeynoteHighlight` WRITE;
/*!40000 ALTER TABLE `SummitKeynoteHighlight` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitKeynoteHighlight` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitLocationBanner`
--

DROP TABLE IF EXISTS `SummitLocationBanner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitLocationBanner` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitLocationBanner','ScheduledSummitLocationBanner') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitLocationBanner',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '0',
  `Type` enum('Primary','Secondary') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Primary',
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitLocationBanner`
--

LOCK TABLES `SummitLocationBanner` WRITE;
/*!40000 ALTER TABLE `SummitLocationBanner` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitLocationBanner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitLocationImage`
--

DROP TABLE IF EXISTS `SummitLocationImage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitLocationImage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitLocationImage','SummitLocationMap') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitLocationImage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '1',
  `PictureID` int DEFAULT NULL,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PictureID` (`PictureID`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitLocationImage`
--

LOCK TABLES `SummitLocationImage` WRITE;
/*!40000 ALTER TABLE `SummitLocationImage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitLocationImage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitLocationPage`
--

DROP TABLE IF EXISTS `SummitLocationPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitLocationPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `VisaInformation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CityIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationsTextHeader` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherLocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GettingAround` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Locals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHero` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHeroSource` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLng` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueTitleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CampusGraphic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueBackgroundImageHero` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageHeroSource` varchar(510) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageID` int DEFAULT NULL,
  `AboutTheCityBackgroundImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VenueBackgroundImageID` (`VenueBackgroundImageID`),
  KEY `AboutTheCityBackgroundImageID` (`AboutTheCityBackgroundImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitLocationPage`
--

LOCK TABLES `SummitLocationPage` WRITE;
/*!40000 ALTER TABLE `SummitLocationPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitLocationPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitLocationPage_Live`
--

DROP TABLE IF EXISTS `SummitLocationPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitLocationPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `VisaInformation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CityIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationsTextHeader` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherLocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GettingAround` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Locals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHero` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHeroSource` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLng` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueTitleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CampusGraphic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueBackgroundImageHero` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageHeroSource` varchar(510) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageID` int DEFAULT NULL,
  `AboutTheCityBackgroundImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VenueBackgroundImageID` (`VenueBackgroundImageID`),
  KEY `AboutTheCityBackgroundImageID` (`AboutTheCityBackgroundImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitLocationPage_Live`
--

LOCK TABLES `SummitLocationPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitLocationPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitLocationPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitLocationPage_versions`
--

DROP TABLE IF EXISTS `SummitLocationPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitLocationPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `VisaInformation` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CityIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationsTextHeader` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OtherLocations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GettingAround` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCity` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Locals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TravelSupport` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHero` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AboutTheCityBackgroundImageHeroSource` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HostCityLng` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueTitleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AirportsSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CampusGraphic` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueBackgroundImageHero` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageHeroSource` varchar(510) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `VenueBackgroundImageID` int DEFAULT NULL,
  `AboutTheCityBackgroundImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `VenueBackgroundImageID` (`VenueBackgroundImageID`),
  KEY `AboutTheCityBackgroundImageID` (`AboutTheCityBackgroundImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitLocationPage_versions`
--

LOCK TABLES `SummitLocationPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitLocationPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitLocationPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitMediaFileType`
--

DROP TABLE IF EXISTS `SummitMediaFileType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitMediaFileType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitMediaFileType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitMediaFileType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsSystemDefine` tinyint(1) NOT NULL,
  `AllowedExtensions` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_827E5F3AFE11D138` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitMediaFileType`
--

LOCK TABLES `SummitMediaFileType` WRITE;
/*!40000 ALTER TABLE `SummitMediaFileType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitMediaFileType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitMediaUploadType`
--

DROP TABLE IF EXISTS `SummitMediaUploadType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitMediaUploadType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitMediaUploadType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitMediaUploadType',
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `Description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MaxSize` int NOT NULL DEFAULT '1024',
  `PrivateStorageType` enum('None','DropBox','Local','Swift') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `PublicStorageType` enum('None','DropBox','S3','Swift','Local') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'None',
  `SummitID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  `UseTemporaryLinksOnPublicStorage` tinyint(1) NOT NULL DEFAULT '0',
  `TemporaryLinksOnPublicStorageTTL` int NOT NULL DEFAULT '0',
  `MinUploadsQty` int NOT NULL DEFAULT '0',
  `MaxUploadsQty` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_1362D86390CF7278FE11D138` (`SummitID`,`Name`),
  KEY `SummitID` (`SummitID`),
  KEY `TypeID` (`TypeID`),
  KEY `IDX_1362D86390CF7278` (`SummitID`),
  KEY `IDX_1362D863A736B16E` (`TypeID`),
  CONSTRAINT `FK_1362D86390CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_1362D863A736B16E` FOREIGN KEY (`TypeID`) REFERENCES `SummitMediaFileType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitMediaUploadType`
--

LOCK TABLES `SummitMediaUploadType` WRITE;
/*!40000 ALTER TABLE `SummitMediaUploadType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitMediaUploadType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitMetric`
--

DROP TABLE IF EXISTS `SummitMetric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitMetric` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitMetric','SummitEventAttendanceMetric','SummitSponsorMetric') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitMetric',
  `Type` enum('GENERAL','LOBBY','SPONSOR','EVENT','POSTER','POSTERS','ROOM') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'GENERAL',
  `Ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Origin` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Browser` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IngressDate` datetime DEFAULT NULL,
  `OutgressDate` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `Location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_D04B9CCF522B9974` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_D04B9CCF90CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=516569 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitMetric`
--

LOCK TABLES `SummitMetric` WRITE;
/*!40000 ALTER TABLE `SummitMetric` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitMetric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitNetworkingPhoto`
--

DROP TABLE IF EXISTS `SummitNetworkingPhoto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitNetworkingPhoto` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitNetworkingPhoto') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitNetworkingPhoto',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `ImageID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ImageID` (`ImageID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitNetworkingPhoto`
--

LOCK TABLES `SummitNetworkingPhoto` WRITE;
/*!40000 ALTER TABLE `SummitNetworkingPhoto` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitNetworkingPhoto` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOrder`
--

DROP TABLE IF EXISTS `SummitOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOrder` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitOrder') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitOrder',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExternalId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PaymentMethod` enum('Online','Offline') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Offline',
  `Status` enum('Reserved','Cancelled','RefundRequested','Refunded','Confirmed','Paid','Error') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Reserved',
  `OwnerFirstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OwnerSurname` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OwnerEmail` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OwnerCompany` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddress1` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddress2` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddressZipCode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddressCity` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddressState` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `BillingAddressCountryISOCode` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ApprovedPaymentDate` datetime DEFAULT NULL,
  `LastError` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PaymentGatewayCartId` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PaymentGatewayClientToken` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `QRCode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HashCreationDate` datetime DEFAULT NULL,
  `RefundedAmount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `SummitID` int DEFAULT NULL,
  `OwnerID` int DEFAULT NULL,
  `OwnerCompanyID` int DEFAULT NULL,
  `LastReminderEmailSentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `CompanyID` (`OwnerCompanyID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_SummitOrder_Company` FOREIGN KEY (`OwnerCompanyID`) REFERENCES `Company` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitOrder_Owner` FOREIGN KEY (`OwnerID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitOrder_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=28153 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOrder`
--

LOCK TABLES `SummitOrder` WRITE;
/*!40000 ALTER TABLE `SummitOrder` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOrderExtraQuestionAnswer`
--

DROP TABLE IF EXISTS `SummitOrderExtraQuestionAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOrderExtraQuestionAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OrderID` int DEFAULT NULL,
  `SummitAttendeeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OrderID` (`OrderID`),
  KEY `SummitAttendeeID` (`SummitAttendeeID`),
  CONSTRAINT `FK_ SummitOrderExtraQuestionAnswer_Attendee` FOREIGN KEY (`SummitAttendeeID`) REFERENCES `SummitAttendee` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitOrderExtraQuestionAnswer_Order` FOREIGN KEY (`OrderID`) REFERENCES `SummitOrder` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `JT_SummitOrderExtraQuestionAnswer_ExtraQuestionAnswer` FOREIGN KEY (`ID`) REFERENCES `ExtraQuestionAnswer` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=381481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOrderExtraQuestionAnswer`
--

LOCK TABLES `SummitOrderExtraQuestionAnswer` WRITE;
/*!40000 ALTER TABLE `SummitOrderExtraQuestionAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOrderExtraQuestionAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOrderExtraQuestionType`
--

DROP TABLE IF EXISTS `SummitOrderExtraQuestionType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOrderExtraQuestionType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Usage` enum('Order','Ticket','Both') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Order',
  `Printable` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `ExternalId` longtext COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `JT_SummitOrderExtraQuestionType_ExtraQuestionType` FOREIGN KEY (`ID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=267 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOrderExtraQuestionType`
--

LOCK TABLES `SummitOrderExtraQuestionType` WRITE;
/*!40000 ALTER TABLE `SummitOrderExtraQuestionType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOrderExtraQuestionType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOverviewPage`
--

DROP TABLE IF EXISTS `SummitOverviewPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOverviewPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OverviewIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextTop` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextBottom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RecapTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleBtnText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NetworkingContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwoMainEventsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees1Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees2Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees3Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees4Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TimelineCaption` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxBackgroundID` int DEFAULT NULL,
  `GrowthBoxChartLegendID` int DEFAULT NULL,
  `GrowthBoxChartLegendPngID` int DEFAULT NULL,
  `GrowthBoxChartID` int DEFAULT NULL,
  `GrowthBoxChartPngID` int DEFAULT NULL,
  `EventOneLogoID` int DEFAULT NULL,
  `EventOneLogoPngID` int DEFAULT NULL,
  `EventTwoLogoID` int DEFAULT NULL,
  `EventTwoLogoPngID` int DEFAULT NULL,
  `Atendees1ChartID` int DEFAULT NULL,
  `Atendees1ChartPngID` int DEFAULT NULL,
  `Atendees2ChartID` int DEFAULT NULL,
  `Atendees2ChartPngID` int DEFAULT NULL,
  `Atendees3ChartID` int DEFAULT NULL,
  `Atendees3ChartPngID` int DEFAULT NULL,
  `Atendees4ChartID` int DEFAULT NULL,
  `Atendees4ChartPngID` int DEFAULT NULL,
  `AtendeesChartRefID` int DEFAULT NULL,
  `AtendeesChartRefPngID` int DEFAULT NULL,
  `TimelineImageID` int DEFAULT NULL,
  `TimelineImagePngID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GrowthBoxBackgroundID` (`GrowthBoxBackgroundID`),
  KEY `GrowthBoxChartLegendID` (`GrowthBoxChartLegendID`),
  KEY `GrowthBoxChartLegendPngID` (`GrowthBoxChartLegendPngID`),
  KEY `GrowthBoxChartID` (`GrowthBoxChartID`),
  KEY `GrowthBoxChartPngID` (`GrowthBoxChartPngID`),
  KEY `EventOneLogoID` (`EventOneLogoID`),
  KEY `EventOneLogoPngID` (`EventOneLogoPngID`),
  KEY `EventTwoLogoID` (`EventTwoLogoID`),
  KEY `EventTwoLogoPngID` (`EventTwoLogoPngID`),
  KEY `Atendees1ChartID` (`Atendees1ChartID`),
  KEY `Atendees1ChartPngID` (`Atendees1ChartPngID`),
  KEY `Atendees2ChartID` (`Atendees2ChartID`),
  KEY `Atendees2ChartPngID` (`Atendees2ChartPngID`),
  KEY `Atendees3ChartID` (`Atendees3ChartID`),
  KEY `Atendees3ChartPngID` (`Atendees3ChartPngID`),
  KEY `Atendees4ChartID` (`Atendees4ChartID`),
  KEY `Atendees4ChartPngID` (`Atendees4ChartPngID`),
  KEY `AtendeesChartRefID` (`AtendeesChartRefID`),
  KEY `AtendeesChartRefPngID` (`AtendeesChartRefPngID`),
  KEY `TimelineImageID` (`TimelineImageID`),
  KEY `TimelineImagePngID` (`TimelineImagePngID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOverviewPage`
--

LOCK TABLES `SummitOverviewPage` WRITE;
/*!40000 ALTER TABLE `SummitOverviewPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOverviewPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOverviewPageHelpMenuItem`
--

DROP TABLE IF EXISTS `SummitOverviewPageHelpMenuItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOverviewPageHelpMenuItem` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitOverviewPageHelpMenuItem') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitOverviewPageHelpMenuItem',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Url` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FAIcon` enum('fa-h-square','fa-comment','fa-tag','fa-question','fa-users','fa-mobile','none','fa-map-signs','fa-map','fa-calendar','fa-bed','fa-beer','fa-cab','fa-compass','fa-cutlery','fa-location-arrow','fa-venus','fa-youtube-play') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'none',
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOverviewPageHelpMenuItem`
--

LOCK TABLES `SummitOverviewPageHelpMenuItem` WRITE;
/*!40000 ALTER TABLE `SummitOverviewPageHelpMenuItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOverviewPageHelpMenuItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOverviewPage_Live`
--

DROP TABLE IF EXISTS `SummitOverviewPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOverviewPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `OverviewIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextTop` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextBottom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RecapTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleBtnText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NetworkingContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwoMainEventsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees1Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees2Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees3Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees4Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TimelineCaption` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxBackgroundID` int DEFAULT NULL,
  `GrowthBoxChartLegendID` int DEFAULT NULL,
  `GrowthBoxChartLegendPngID` int DEFAULT NULL,
  `GrowthBoxChartID` int DEFAULT NULL,
  `GrowthBoxChartPngID` int DEFAULT NULL,
  `EventOneLogoID` int DEFAULT NULL,
  `EventOneLogoPngID` int DEFAULT NULL,
  `EventTwoLogoID` int DEFAULT NULL,
  `EventTwoLogoPngID` int DEFAULT NULL,
  `Atendees1ChartID` int DEFAULT NULL,
  `Atendees1ChartPngID` int DEFAULT NULL,
  `Atendees2ChartID` int DEFAULT NULL,
  `Atendees2ChartPngID` int DEFAULT NULL,
  `Atendees3ChartID` int DEFAULT NULL,
  `Atendees3ChartPngID` int DEFAULT NULL,
  `Atendees4ChartID` int DEFAULT NULL,
  `Atendees4ChartPngID` int DEFAULT NULL,
  `AtendeesChartRefID` int DEFAULT NULL,
  `AtendeesChartRefPngID` int DEFAULT NULL,
  `TimelineImageID` int DEFAULT NULL,
  `TimelineImagePngID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `GrowthBoxBackgroundID` (`GrowthBoxBackgroundID`),
  KEY `GrowthBoxChartLegendID` (`GrowthBoxChartLegendID`),
  KEY `GrowthBoxChartLegendPngID` (`GrowthBoxChartLegendPngID`),
  KEY `GrowthBoxChartID` (`GrowthBoxChartID`),
  KEY `GrowthBoxChartPngID` (`GrowthBoxChartPngID`),
  KEY `EventOneLogoID` (`EventOneLogoID`),
  KEY `EventOneLogoPngID` (`EventOneLogoPngID`),
  KEY `EventTwoLogoID` (`EventTwoLogoID`),
  KEY `EventTwoLogoPngID` (`EventTwoLogoPngID`),
  KEY `Atendees1ChartID` (`Atendees1ChartID`),
  KEY `Atendees1ChartPngID` (`Atendees1ChartPngID`),
  KEY `Atendees2ChartID` (`Atendees2ChartID`),
  KEY `Atendees2ChartPngID` (`Atendees2ChartPngID`),
  KEY `Atendees3ChartID` (`Atendees3ChartID`),
  KEY `Atendees3ChartPngID` (`Atendees3ChartPngID`),
  KEY `Atendees4ChartID` (`Atendees4ChartID`),
  KEY `Atendees4ChartPngID` (`Atendees4ChartPngID`),
  KEY `AtendeesChartRefID` (`AtendeesChartRefID`),
  KEY `AtendeesChartRefPngID` (`AtendeesChartRefPngID`),
  KEY `TimelineImageID` (`TimelineImageID`),
  KEY `TimelineImagePngID` (`TimelineImagePngID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOverviewPage_Live`
--

LOCK TABLES `SummitOverviewPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitOverviewPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOverviewPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitOverviewPage_versions`
--

DROP TABLE IF EXISTS `SummitOverviewPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitOverviewPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `OverviewIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextTop` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxTextBottom` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RecapTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID1` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapCaption2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VideoRecapYouTubeID2` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleUrl` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ScheduleBtnText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NetworkingContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwoMainEventsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventOneContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoSubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `EventTwoContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees1Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees2Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees3Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Atendees4Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TimelineCaption` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GrowthBoxBackgroundID` int DEFAULT NULL,
  `GrowthBoxChartLegendID` int DEFAULT NULL,
  `GrowthBoxChartLegendPngID` int DEFAULT NULL,
  `GrowthBoxChartID` int DEFAULT NULL,
  `GrowthBoxChartPngID` int DEFAULT NULL,
  `EventOneLogoID` int DEFAULT NULL,
  `EventOneLogoPngID` int DEFAULT NULL,
  `EventTwoLogoID` int DEFAULT NULL,
  `EventTwoLogoPngID` int DEFAULT NULL,
  `Atendees1ChartID` int DEFAULT NULL,
  `Atendees1ChartPngID` int DEFAULT NULL,
  `Atendees2ChartID` int DEFAULT NULL,
  `Atendees2ChartPngID` int DEFAULT NULL,
  `Atendees3ChartID` int DEFAULT NULL,
  `Atendees3ChartPngID` int DEFAULT NULL,
  `Atendees4ChartID` int DEFAULT NULL,
  `Atendees4ChartPngID` int DEFAULT NULL,
  `AtendeesChartRefID` int DEFAULT NULL,
  `AtendeesChartRefPngID` int DEFAULT NULL,
  `TimelineImageID` int DEFAULT NULL,
  `TimelineImagePngID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `GrowthBoxBackgroundID` (`GrowthBoxBackgroundID`),
  KEY `GrowthBoxChartLegendID` (`GrowthBoxChartLegendID`),
  KEY `GrowthBoxChartLegendPngID` (`GrowthBoxChartLegendPngID`),
  KEY `GrowthBoxChartID` (`GrowthBoxChartID`),
  KEY `GrowthBoxChartPngID` (`GrowthBoxChartPngID`),
  KEY `EventOneLogoID` (`EventOneLogoID`),
  KEY `EventOneLogoPngID` (`EventOneLogoPngID`),
  KEY `EventTwoLogoID` (`EventTwoLogoID`),
  KEY `EventTwoLogoPngID` (`EventTwoLogoPngID`),
  KEY `Atendees1ChartID` (`Atendees1ChartID`),
  KEY `Atendees1ChartPngID` (`Atendees1ChartPngID`),
  KEY `Atendees2ChartID` (`Atendees2ChartID`),
  KEY `Atendees2ChartPngID` (`Atendees2ChartPngID`),
  KEY `Atendees3ChartID` (`Atendees3ChartID`),
  KEY `Atendees3ChartPngID` (`Atendees3ChartPngID`),
  KEY `Atendees4ChartID` (`Atendees4ChartID`),
  KEY `Atendees4ChartPngID` (`Atendees4ChartPngID`),
  KEY `AtendeesChartRefID` (`AtendeesChartRefID`),
  KEY `AtendeesChartRefPngID` (`AtendeesChartRefPngID`),
  KEY `TimelineImageID` (`TimelineImageID`),
  KEY `TimelineImagePngID` (`TimelineImagePngID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitOverviewPage_versions`
--

LOCK TABLES `SummitOverviewPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitOverviewPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitOverviewPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPackage`
--

DROP TABLE IF EXISTS `SummitPackage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPackage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitPackage') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitPackage',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SubTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Cost` decimal(9,2) NOT NULL DEFAULT '0.00',
  `MaxAvailable` int NOT NULL DEFAULT '0',
  `CurrentlyAvailable` int NOT NULL DEFAULT '0',
  `Order` int NOT NULL DEFAULT '0',
  `ShowQuantity` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPackage`
--

LOCK TABLES `SummitPackage` WRITE;
/*!40000 ALTER TABLE `SummitPackage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPackage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPackagePurchaseOrder`
--

DROP TABLE IF EXISTS `SummitPackagePurchaseOrder`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPackagePurchaseOrder` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitPackagePurchaseOrder') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitPackagePurchaseOrder',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FirstName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Surname` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Email` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Organization` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Approved` tinyint unsigned NOT NULL DEFAULT '0',
  `ApprovedDate` datetime DEFAULT NULL,
  `Rejected` tinyint unsigned NOT NULL DEFAULT '0',
  `RejectedDate` datetime DEFAULT NULL,
  `RegisteredOrganizationID` int DEFAULT NULL,
  `ApprovedByID` int DEFAULT NULL,
  `RejectedByID` int DEFAULT NULL,
  `PackageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RegisteredOrganizationID` (`RegisteredOrganizationID`),
  KEY `ApprovedByID` (`ApprovedByID`),
  KEY `RejectedByID` (`RejectedByID`),
  KEY `PackageID` (`PackageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPackagePurchaseOrder`
--

LOCK TABLES `SummitPackagePurchaseOrder` WRITE;
/*!40000 ALTER TABLE `SummitPackagePurchaseOrder` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPackagePurchaseOrder` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPackage_DiscountPackages`
--

DROP TABLE IF EXISTS `SummitPackage_DiscountPackages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPackage_DiscountPackages` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitPackageID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  `Discount` decimal(5,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`ID`),
  KEY `SummitPackageID` (`SummitPackageID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPackage_DiscountPackages`
--

LOCK TABLES `SummitPackage_DiscountPackages` WRITE;
/*!40000 ALTER TABLE `SummitPackage_DiscountPackages` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPackage_DiscountPackages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPage`
--

DROP TABLE IF EXISTS `SummitPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroCSSClass` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksLeft` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksRight` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitImageID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitImageID` (`SummitImageID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPage`
--

LOCK TABLES `SummitPage` WRITE;
/*!40000 ALTER TABLE `SummitPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPage_Live`
--

DROP TABLE IF EXISTS `SummitPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroCSSClass` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksLeft` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksRight` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitImageID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitImageID` (`SummitImageID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPage_Live`
--

LOCK TABLES `SummitPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPage_versions`
--

DROP TABLE IF EXISTS `SummitPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `GAConversionId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLanguage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionFormat` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionColor` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `GAConversionValue` int NOT NULL DEFAULT '0',
  `GARemarketingOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `FBPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `TwitterPixelId` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroCSSClass` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeaderMessage` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksLeft` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FooterLinksRight` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitImageID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `SummitImageID` (`SummitImageID`),
  KEY `SummitID` (`SummitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPage_versions`
--

LOCK TABLES `SummitPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPieDataItem`
--

DROP TABLE IF EXISTS `SummitPieDataItem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPieDataItem` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitPieDataItem','SummitPieDataItemRegion','SummitPieDataItemRole') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitPieDataItem',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Color` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPieDataItem`
--

LOCK TABLES `SummitPieDataItem` WRITE;
/*!40000 ALTER TABLE `SummitPieDataItem` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPieDataItem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPresentationComment`
--

DROP TABLE IF EXISTS `SummitPresentationComment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPresentationComment` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitPresentationComment') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitPresentationComment',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsActivity` tinyint unsigned NOT NULL DEFAULT '0',
  `IsPublic` tinyint unsigned NOT NULL DEFAULT '0',
  `PresentationID` int DEFAULT NULL,
  `CommenterID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `CommenterID` (`CommenterID`),
  KEY `ClassName` (`ClassName`),
  CONSTRAINT `FK_SummitPresentationComment_CommenterID` FOREIGN KEY (`CommenterID`) REFERENCES `Member` (`ID`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `FK_SummitPresentationComment_Presentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=6639 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPresentationComment`
--

LOCK TABLES `SummitPresentationComment` WRITE;
/*!40000 ALTER TABLE `SummitPresentationComment` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPresentationComment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitProposedSchedule`
--

DROP TABLE IF EXISTS `SummitProposedSchedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitProposedSchedule` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitProposedSchedule',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NULL',
  `Source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'track-chairs',
  `SummitID` int DEFAULT NULL,
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitID_Source` (`SummitID`,`Source`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitID` (`SummitID`),
  KEY `CreatedByID` (`CreatedByID`),
  CONSTRAINT `FK_SummitProposedSchedule_CreatedBy` FOREIGN KEY (`CreatedByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitProposedSchedule_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitProposedSchedule`
--

LOCK TABLES `SummitProposedSchedule` WRITE;
/*!40000 ALTER TABLE `SummitProposedSchedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitProposedSchedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitProposedScheduleSummitEvent`
--

DROP TABLE IF EXISTS `SummitProposedScheduleSummitEvent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitProposedScheduleSummitEvent` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitProposedScheduleSummitEvent',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `StartDate` datetime NOT NULL,
  `EndDate` datetime NOT NULL,
  `Duration` int NOT NULL DEFAULT '0',
  `ScheduleID` int unsigned NOT NULL,
  `SummitEventID` int NOT NULL,
  `LocationID` int DEFAULT NULL,
  `CreatedByID` int DEFAULT NULL,
  `UpdatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `IDX_SummitProposedScheduleSummitEvent_Event_Unique` (`ScheduleID`,`SummitEventID`),
  KEY `ClassName` (`ClassName`),
  KEY `ScheduleID` (`ScheduleID`),
  KEY `SummitEventID` (`SummitEventID`),
  KEY `LocationID` (`LocationID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `UpdatedByID` (`UpdatedByID`),
  CONSTRAINT `FK_SummitProposedScheduleSummitEvent_CreatedBy` FOREIGN KEY (`CreatedByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitProposedScheduleSummitEvent_Event` FOREIGN KEY (`SummitEventID`) REFERENCES `SummitEvent` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitProposedScheduleSummitEvent_Location` FOREIGN KEY (`LocationID`) REFERENCES `SummitAbstractLocation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitProposedScheduleSummitEvent_Schedule` FOREIGN KEY (`ScheduleID`) REFERENCES `SummitProposedSchedule` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitProposedScheduleSummitEvent_UpdatedBy` FOREIGN KEY (`UpdatedByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitProposedScheduleSummitEvent`
--

LOCK TABLES `SummitProposedScheduleSummitEvent` WRITE;
/*!40000 ALTER TABLE `SummitProposedScheduleSummitEvent` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitProposedScheduleSummitEvent` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPushNotification`
--

DROP TABLE IF EXISTS `SummitPushNotification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPushNotification` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Channel` enum('EVERYONE','SPEAKERS','ATTENDEES','MEMBERS','SUMMIT','EVENT','GROUP') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EVERYONE',
  `SummitID` int DEFAULT NULL,
  `EventID` int DEFAULT NULL,
  `GroupID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `EventID` (`EventID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPushNotification`
--

LOCK TABLES `SummitPushNotification` WRITE;
/*!40000 ALTER TABLE `SummitPushNotification` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPushNotification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitPushNotification_Recipients`
--

DROP TABLE IF EXISTS `SummitPushNotification_Recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitPushNotification_Recipients` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitPushNotificationID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitPushNotificationID` (`SummitPushNotificationID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitPushNotification_Recipients`
--

LOCK TABLES `SummitPushNotification_Recipients` WRITE;
/*!40000 ALTER TABLE `SummitPushNotification_Recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitPushNotification_Recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitQuestion`
--

DROP TABLE IF EXISTS `SummitQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitQuestion') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitQuestion',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `Question` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Answer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ExtendedAnswer` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitQuestionsPageID` int DEFAULT NULL,
  `CategoryID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitQuestionsPageID` (`SummitQuestionsPageID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitQuestion`
--

LOCK TABLES `SummitQuestion` WRITE;
/*!40000 ALTER TABLE `SummitQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitQuestionCategory`
--

DROP TABLE IF EXISTS `SummitQuestionCategory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitQuestionCategory` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitQuestionCategory') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitQuestionCategory',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitQuestionsPageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitQuestionsPageID` (`SummitQuestionsPageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitQuestionCategory`
--

LOCK TABLES `SummitQuestionCategory` WRITE;
/*!40000 ALTER TABLE `SummitQuestionCategory` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitQuestionCategory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRefundPolicyType`
--

DROP TABLE IF EXISTS `SummitRefundPolicyType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRefundPolicyType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitRefundPolicyType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitRefundPolicyType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `UntilXDaysBeforeEventStarts` int NOT NULL DEFAULT '0',
  `RefundRate` decimal(9,2) NOT NULL DEFAULT '0.00',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRefundPolicyType`
--

LOCK TABLES `SummitRefundPolicyType` WRITE;
/*!40000 ALTER TABLE `SummitRefundPolicyType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRefundPolicyType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRefundRequest`
--

DROP TABLE IF EXISTS `SummitRefundRequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRefundRequest` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitRefundRequest','SummitAttendeeTicketRefundRequest') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitRefundRequest',
  `RefundedAmount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `Notes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ActionDate` datetime DEFAULT NULL,
  `Status` enum('Requested','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Requested',
  `PaymentGatewayResult` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `RequestedByID` int DEFAULT NULL,
  `ActionByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `RequestedByID` (`RequestedByID`),
  KEY `ActionByID` (`ActionByID`),
  CONSTRAINT `FK_44392ED424BFE9DA` FOREIGN KEY (`ActionByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_44392ED4DB2F4727` FOREIGN KEY (`RequestedByID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=281 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRefundRequest`
--

LOCK TABLES `SummitRefundRequest` WRITE;
/*!40000 ALTER TABLE `SummitRefundRequest` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRefundRequest` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationDiscountCode`
--

DROP TABLE IF EXISTS `SummitRegistrationDiscountCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationDiscountCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `DiscountRate` decimal(9,2) NOT NULL DEFAULT '0.00',
  `DiscountAmount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_SummitRegistrationDiscountCode_PromoCode` FOREIGN KEY (`ID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1029 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationDiscountCode`
--

LOCK TABLES `SummitRegistrationDiscountCode` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationDiscountCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationDiscountCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationDiscountCode_AllowedTicketTypes`
--

DROP TABLE IF EXISTS `SummitRegistrationDiscountCode_AllowedTicketTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationDiscountCode_AllowedTicketTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitRegistrationDiscountCodeID` int NOT NULL DEFAULT '0',
  `SummitTicketTypeID` int NOT NULL DEFAULT '0',
  `DiscountRate` decimal(9,2) NOT NULL DEFAULT '0.00',
  `DiscountAmount` decimal(19,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`ID`),
  KEY `SummitRegistrationDiscountCodeID` (`SummitRegistrationDiscountCodeID`),
  KEY `SummitTicketTypeID` (`SummitTicketTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=159 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationDiscountCode_AllowedTicketTypes`
--

LOCK TABLES `SummitRegistrationDiscountCode_AllowedTicketTypes` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationDiscountCode_AllowedTicketTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationDiscountCode_AllowedTicketTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationInvitation`
--

DROP TABLE IF EXISTS `SummitRegistrationInvitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationInvitation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitRegistrationInvitation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitRegistrationInvitation',
  `Hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `AcceptedDate` datetime DEFAULT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `FirstName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `LastName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `SetPasswordLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `SummitOrderID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Hash` (`Hash`),
  UNIQUE KEY `UNIQ_ACF9E7B82653537090CF7278` (`Email`,`SummitID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitID` (`SummitID`),
  KEY `SummitOrderID` (`SummitOrderID`),
  CONSTRAINT `FK_ACF9E7B8522B9974` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_ACF9E7B890CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_ACF9E7B8F3C2A5AE` FOREIGN KEY (`SummitOrderID`) REFERENCES `SummitOrder` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6748 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationInvitation`
--

LOCK TABLES `SummitRegistrationInvitation` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationInvitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationInvitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationInvitation_SummitOrders`
--

DROP TABLE IF EXISTS `SummitRegistrationInvitation_SummitOrders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationInvitation_SummitOrders` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitOrderID` int DEFAULT NULL,
  `SummitRegistrationInvitationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_681E6FEFF3C2A5AE3A19CA8` (`SummitOrderID`,`SummitRegistrationInvitationID`),
  KEY `SummitOrderID` (`SummitOrderID`),
  KEY `SummitRegistrationInvitationID` (`SummitRegistrationInvitationID`),
  CONSTRAINT `FK_681E6FEF3A19CA8` FOREIGN KEY (`SummitRegistrationInvitationID`) REFERENCES `SummitRegistrationInvitation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_681E6FEFF3C2A5AE` FOREIGN KEY (`SummitOrderID`) REFERENCES `SummitOrder` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3530 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationInvitation_SummitOrders`
--

LOCK TABLES `SummitRegistrationInvitation_SummitOrders` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_SummitOrders` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_SummitOrders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationInvitation_SummitTicketTypes`
--

DROP TABLE IF EXISTS `SummitRegistrationInvitation_SummitTicketTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationInvitation_SummitTicketTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitTicketTypeID` int DEFAULT NULL,
  `SummitRegistrationInvitationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_76A2AA29398EA10C3A19CA8` (`SummitTicketTypeID`,`SummitRegistrationInvitationID`),
  KEY `SummitTicketTypeID` (`SummitTicketTypeID`),
  KEY `SummitRegistrationInvitationID` (`SummitRegistrationInvitationID`)
) ENGINE=InnoDB AUTO_INCREMENT=11721 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationInvitation_SummitTicketTypes`
--

LOCK TABLES `SummitRegistrationInvitation_SummitTicketTypes` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_SummitTicketTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_SummitTicketTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationInvitation_Tags`
--

DROP TABLE IF EXISTS `SummitRegistrationInvitation_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationInvitation_Tags` (
  `ID` bigint NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `SummitRegistrationInvitationID` int DEFAULT NULL,
  `TagID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_4113CAED3A19CA895B9A210` (`SummitRegistrationInvitationID`,`TagID`),
  KEY `SummitRegistrationInvitationID` (`SummitRegistrationInvitationID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_SummitRegistrationInvitation_Tags_Invitation` FOREIGN KEY (`SummitRegistrationInvitationID`) REFERENCES `SummitRegistrationInvitation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitRegistrationInvitation_Tags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationInvitation_Tags`
--

LOCK TABLES `SummitRegistrationInvitation_Tags` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationInvitation_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationPromoCode`
--

DROP TABLE IF EXISTS `SummitRegistrationPromoCode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationPromoCode` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitRegistrationPromoCode','MemberSummitRegistrationPromoCode','SponsorSummitRegistrationPromoCode','SpeakerSummitRegistrationPromoCode','SummitRegistrationDiscountCode','MemberSummitRegistrationDiscountCode','SponsorSummitRegistrationDiscountCode','SpeakerSummitRegistrationDiscountCode') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitRegistrationPromoCode',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EmailSent` tinyint unsigned NOT NULL DEFAULT '0',
  `Redeemed` tinyint unsigned NOT NULL DEFAULT '0',
  `Source` enum('CSV','ADMIN') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'CSV',
  `EmailSentDate` datetime DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `CreatorID` int DEFAULT NULL,
  `ExternalId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `QuantityAvailable` int NOT NULL DEFAULT '0',
  `QuantityUsed` int NOT NULL DEFAULT '0',
  `ValidSinceDate` datetime DEFAULT NULL,
  `ValidUntilDate` datetime DEFAULT NULL,
  `BadgeTypeID` int DEFAULT NULL,
  `Description` longtext COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitID_Code` (`SummitID`,`Code`),
  KEY `SummitID` (`SummitID`),
  KEY `CreatorID` (`CreatorID`),
  KEY `ClassName` (`ClassName`),
  KEY `BadgeTypeID` (`BadgeTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=1029 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationPromoCode`
--

LOCK TABLES `SummitRegistrationPromoCode` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationPromoCode_AllowedTicketTypes`
--

DROP TABLE IF EXISTS `SummitRegistrationPromoCode_AllowedTicketTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationPromoCode_AllowedTicketTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitRegistrationPromoCodeID` int NOT NULL DEFAULT '0',
  `SummitTicketTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitRegistrationPromoCodeID` (`SummitRegistrationPromoCodeID`),
  KEY `SummitTicketTypeID` (`SummitTicketTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=107 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationPromoCode_AllowedTicketTypes`
--

LOCK TABLES `SummitRegistrationPromoCode_AllowedTicketTypes` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_AllowedTicketTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_AllowedTicketTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationPromoCode_BadgeFeatures`
--

DROP TABLE IF EXISTS `SummitRegistrationPromoCode_BadgeFeatures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationPromoCode_BadgeFeatures` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitRegistrationPromoCodeID` int NOT NULL DEFAULT '0',
  `SummitBadgeFeatureTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitRegistrationPromoCodeID` (`SummitRegistrationPromoCodeID`),
  KEY `SummitBadgeFeatureTypeID` (`SummitBadgeFeatureTypeID`)
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationPromoCode_BadgeFeatures`
--

LOCK TABLES `SummitRegistrationPromoCode_BadgeFeatures` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_BadgeFeatures` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_BadgeFeatures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRegistrationPromoCode_Tags`
--

DROP TABLE IF EXISTS `SummitRegistrationPromoCode_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRegistrationPromoCode_Tags` (
  `ID` bigint NOT NULL AUTO_INCREMENT,
  `SummitRegistrationPromoCodeID` int DEFAULT NULL,
  `TagID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_685F784A1710EC5195B9A210` (`SummitRegistrationPromoCodeID`,`TagID`),
  KEY `SummitRegistrationPromoCodeID` (`SummitRegistrationPromoCodeID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_SummitRegistrationPromoCode_Tags_PromoCode` FOREIGN KEY (`SummitRegistrationPromoCodeID`) REFERENCES `SummitRegistrationPromoCode` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitRegistrationPromoCode_Tags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRegistrationPromoCode_Tags`
--

LOCK TABLES `SummitRegistrationPromoCode_Tags` WRITE;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRegistrationPromoCode_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitReport`
--

DROP TABLE IF EXISTS `SummitReport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitReport` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitReport') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitReport',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitReport`
--

LOCK TABLES `SummitReport` WRITE;
/*!40000 ALTER TABLE `SummitReport` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitReport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitReportConfig`
--

DROP TABLE IF EXISTS `SummitReportConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitReportConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitReportConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitReportConfig',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReportID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReportID` (`ReportID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitReportConfig`
--

LOCK TABLES `SummitReportConfig` WRITE;
/*!40000 ALTER TABLE `SummitReportConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitReportConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitRoomReservation`
--

DROP TABLE IF EXISTS `SummitRoomReservation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitRoomReservation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitRoomReservation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitRoomReservation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `StartDateTime` datetime DEFAULT NULL,
  `EndDateTime` datetime DEFAULT NULL,
  `Status` enum('Reserved','Error','Paid','RequestedRefund','Refunded','Canceled') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Reserved',
  `PaymentGatewayCartId` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `PaymentGatewayClientToken` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Amount` int NOT NULL DEFAULT '0',
  `RefundedAmount` int NOT NULL DEFAULT '0',
  `ApprovedPaymentDate` datetime DEFAULT NULL,
  `LastError` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OwnerID` int DEFAULT NULL,
  `RoomID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `RoomID` (`RoomID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitRoomReservation`
--

LOCK TABLES `SummitRoomReservation` WRITE;
/*!40000 ALTER TABLE `SummitRoomReservation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitRoomReservation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitScheduleConfig`
--

DROP TABLE IF EXISTS `SummitScheduleConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitScheduleConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitScheduleConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitScheduleConfig',
  `Key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'Default',
  `ColorSource` enum('EVENT_TYPES','TRACK','TRACK_GROUP') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'EVENT_TYPES',
  `IsEnabled` tinyint(1) NOT NULL DEFAULT '1',
  `IsMySchedule` tinyint(1) NOT NULL DEFAULT '0',
  `OnlyEventsWithAttendeeAccess` tinyint(1) NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  `IsDefault` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Summit_Key` (`SummitID`,`Key`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_97BF395C90CF7278` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitScheduleConfig`
--

LOCK TABLES `SummitScheduleConfig` WRITE;
/*!40000 ALTER TABLE `SummitScheduleConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitScheduleConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitScheduleFilterElementConfig`
--

DROP TABLE IF EXISTS `SummitScheduleFilterElementConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitScheduleFilterElementConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitScheduleFilterElementConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitScheduleFilterElementConfig',
  `Type` enum('DATE','TRACK','TRACK_GROUPS','COMPANY','LEVEL','SPEAKERS','VENUES','EVENT_TYPES','TITLE','CUSTOM_ORDER','ABSTRACT','TAGS') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DATE',
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `IsEnabled` tinyint(1) NOT NULL DEFAULT '1',
  `PrefilterValues` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitScheduleConfigID` int DEFAULT NULL,
  `CustomOrder` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitScheduleConfig_Type` (`SummitScheduleConfigID`,`Type`),
  KEY `SummitScheduleConfigID` (`SummitScheduleConfigID`),
  CONSTRAINT `FK_F95F239058D86ED5` FOREIGN KEY (`SummitScheduleConfigID`) REFERENCES `SummitScheduleConfig` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=985 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitScheduleFilterElementConfig`
--

LOCK TABLES `SummitScheduleFilterElementConfig` WRITE;
/*!40000 ALTER TABLE `SummitScheduleFilterElementConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitScheduleFilterElementConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitScheduleGlobalSearchTerm`
--

DROP TABLE IF EXISTS `SummitScheduleGlobalSearchTerm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitScheduleGlobalSearchTerm` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitScheduleGlobalSearchTerm') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitScheduleGlobalSearchTerm',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Term` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Hits` int NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitScheduleGlobalSearchTerm`
--

LOCK TABLES `SummitScheduleGlobalSearchTerm` WRITE;
/*!40000 ALTER TABLE `SummitScheduleGlobalSearchTerm` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitScheduleGlobalSearchTerm` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSchedulePreFilterElementConfig`
--

DROP TABLE IF EXISTS `SummitSchedulePreFilterElementConfig`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSchedulePreFilterElementConfig` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `ClassName` enum('SummitSchedulePreFilterElementConfig') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitSchedulePreFilterElementConfig',
  `Type` enum('DATE','TRACK','TRACK_GROUPS','COMPANY','LEVEL','SPEAKERS','VENUES','EVENT_TYPES','TITLE','CUSTOM_ORDER','ABSTRACT','TAGS') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'DATE',
  `Values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitScheduleConfigID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitScheduleConfig_Type` (`SummitScheduleConfigID`,`Type`),
  KEY `SummitScheduleConfigID` (`SummitScheduleConfigID`),
  CONSTRAINT `FK_AC25329C58D86ED5` FOREIGN KEY (`SummitScheduleConfigID`) REFERENCES `SummitScheduleConfig` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=985 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSchedulePreFilterElementConfig`
--

LOCK TABLES `SummitSchedulePreFilterElementConfig` WRITE;
/*!40000 ALTER TABLE `SummitSchedulePreFilterElementConfig` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSchedulePreFilterElementConfig` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSelectedPresentation`
--

DROP TABLE IF EXISTS `SummitSelectedPresentation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSelectedPresentation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitSelectedPresentation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitSelectedPresentation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Collection` enum('maybe','selected','pass') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'maybe',
  `SummitSelectedPresentationListID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitSelectedPresentationListID` (`SummitSelectedPresentationListID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitSelectedPresentation_Presentation_List_Unique` (`PresentationID`,`SummitSelectedPresentationListID`),
  CONSTRAINT `FK_SummitSelectedPresentation_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSelectedPresentation_Presentation` FOREIGN KEY (`PresentationID`) REFERENCES `Presentation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSelectedPresentation_SummitSelectedPresentationList` FOREIGN KEY (`SummitSelectedPresentationListID`) REFERENCES `SummitSelectedPresentationList` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9032 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSelectedPresentation`
--

LOCK TABLES `SummitSelectedPresentation` WRITE;
/*!40000 ALTER TABLE `SummitSelectedPresentation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSelectedPresentation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSelectedPresentationList`
--

DROP TABLE IF EXISTS `SummitSelectedPresentationList`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSelectedPresentationList` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitSelectedPresentationList') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitSelectedPresentationList',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ListType` enum('Individual','Group') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Individual',
  `ListClass` enum('Session','Lightning') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Session',
  `Hash` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CategoryID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_SummitSelectedPresentationList_Member` FOREIGN KEY (`MemberID`) REFERENCES `Member` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSelectedPresentationList_SelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSelectedPresentationList_Track` FOREIGN KEY (`CategoryID`) REFERENCES `PresentationCategory` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=897 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSelectedPresentationList`
--

LOCK TABLES `SummitSelectedPresentationList` WRITE;
/*!40000 ALTER TABLE `SummitSelectedPresentationList` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSelectedPresentationList` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSelectionPlanExtraQuestionType`
--

DROP TABLE IF EXISTS `SummitSelectionPlanExtraQuestionType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSelectionPlanExtraQuestionType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SelectionPlanID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_7AA38C2FB172E6EC` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSelectionPlanExtraQuestionType_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `JT_SummitSelectionPlanExtraQuestionType_ExtraQuestionType` FOREIGN KEY (`ID`) REFERENCES `ExtraQuestionType` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=255 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSelectionPlanExtraQuestionType`
--

LOCK TABLES `SummitSelectionPlanExtraQuestionType` WRITE;
/*!40000 ALTER TABLE `SummitSelectionPlanExtraQuestionType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSelectionPlanExtraQuestionType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSelectionPlanExtraQuestionType_SelectionPlan`
--

DROP TABLE IF EXISTS `SummitSelectionPlanExtraQuestionType_SelectionPlan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSelectionPlanExtraQuestionType_SelectionPlan` (
  `ID` bigint NOT NULL AUTO_INCREMENT,
  `CustomOrder` smallint NOT NULL DEFAULT '1',
  `SummitSelectionPlanExtraQuestionTypeID` int DEFAULT NULL,
  `SelectionPlanID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_AB06C55CE7C1EEFFB172E6EC` (`SummitSelectionPlanExtraQuestionTypeID`,`SelectionPlanID`),
  KEY `SummitSelectionPlanExtraQuestionTypeID` (`SummitSelectionPlanExtraQuestionTypeID`),
  KEY `SelectionPlanID` (`SelectionPlanID`),
  CONSTRAINT `FK_AssignedSelectionPlan_Question_Type` FOREIGN KEY (`SummitSelectionPlanExtraQuestionTypeID`) REFERENCES `SummitSelectionPlanExtraQuestionType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_AssignedSelectionPlan_SelectionPlan` FOREIGN KEY (`SelectionPlanID`) REFERENCES `SelectionPlan` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSelectionPlanExtraQuestionType_SelectionPlan`
--

LOCK TABLES `SummitSelectionPlanExtraQuestionType_SelectionPlan` WRITE;
/*!40000 ALTER TABLE `SummitSelectionPlanExtraQuestionType_SelectionPlan` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSelectionPlanExtraQuestionType_SelectionPlan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSponsorMetric`
--

DROP TABLE IF EXISTS `SummitSponsorMetric`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSponsorMetric` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitSponsorMetric',
  `SponsorID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SponsorID` (`SponsorID`),
  CONSTRAINT `FK_8AFBB25E94CE1A1A` FOREIGN KEY (`SponsorID`) REFERENCES `Sponsor` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSponsorMetricc_SummitMetric` FOREIGN KEY (`ID`) REFERENCES `SummitMetric` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=515519 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSponsorMetric`
--

LOCK TABLES `SummitSponsorMetric` WRITE;
/*!40000 ALTER TABLE `SummitSponsorMetric` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSponsorMetric` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSponsorPage`
--

DROP TABLE IF EXISTS `SummitSponsorPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSponsorPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorAlert` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorContract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorProspectus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CallForSponsorShipStartDate` datetime DEFAULT NULL,
  `CallForSponsorShipEndDate` datetime DEFAULT NULL,
  `AudienceIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowAudience` tinyint unsigned NOT NULL DEFAULT '0',
  `AudienceMetricsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceTotalSummitAttendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCompaniesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCountriesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HowToSponsorContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueMapContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ConditionalSponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipAddOnsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CrowdImageID` int DEFAULT NULL,
  `ExhibitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CrowdImageID` (`CrowdImageID`),
  KEY `ExhibitImageID` (`ExhibitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSponsorPage`
--

LOCK TABLES `SummitSponsorPage` WRITE;
/*!40000 ALTER TABLE `SummitSponsorPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSponsorPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSponsorPage_Live`
--

DROP TABLE IF EXISTS `SummitSponsorPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSponsorPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SponsorIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorAlert` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorContract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorProspectus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CallForSponsorShipStartDate` datetime DEFAULT NULL,
  `CallForSponsorShipEndDate` datetime DEFAULT NULL,
  `AudienceIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowAudience` tinyint unsigned NOT NULL DEFAULT '0',
  `AudienceMetricsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceTotalSummitAttendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCompaniesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCountriesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HowToSponsorContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueMapContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ConditionalSponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipAddOnsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CrowdImageID` int DEFAULT NULL,
  `ExhibitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CrowdImageID` (`CrowdImageID`),
  KEY `ExhibitImageID` (`ExhibitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSponsorPage_Live`
--

LOCK TABLES `SummitSponsorPage_Live` WRITE;
/*!40000 ALTER TABLE `SummitSponsorPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSponsorPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSponsorPage_versions`
--

DROP TABLE IF EXISTS `SummitSponsorPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSponsorPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `SponsorIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorAlert` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorContract` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorProspectus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CallForSponsorShipStartDate` datetime DEFAULT NULL,
  `CallForSponsorShipEndDate` datetime DEFAULT NULL,
  `AudienceIntro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShowAudience` tinyint unsigned NOT NULL DEFAULT '0',
  `AudienceMetricsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceTotalSummitAttendees` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCompaniesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AudienceCountriesRepresented` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HowToSponsorContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `VenueMapContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ConditionalSponsorshipPackagesTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SponsorshipAddOnsTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CrowdImageID` int DEFAULT NULL,
  `ExhibitImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `CrowdImageID` (`CrowdImageID`),
  KEY `ExhibitImageID` (`ExhibitImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSponsorPage_versions`
--

LOCK TABLES `SummitSponsorPage_versions` WRITE;
/*!40000 ALTER TABLE `SummitSponsorPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSponsorPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSubmissionInvitation`
--

DROP TABLE IF EXISTS `SummitSubmissionInvitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSubmissionInvitation` (
  `ID` int unsigned NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'SummitSubmissionInvitation',
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs NOT NULL,
  `FirstName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NULL',
  `LastName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NULL',
  `SentDate` datetime DEFAULT NULL,
  `OTP` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'NULL',
  `SummitID` int DEFAULT NULL,
  `SpeakerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`),
  KEY `SummitID` (`SummitID`),
  KEY `SpeakerID` (`SpeakerID`),
  CONSTRAINT `FK_SummitSubmissionInvitation_Speaker` FOREIGN KEY (`SpeakerID`) REFERENCES `PresentationSpeaker` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSubmissionInvitation_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSubmissionInvitation`
--

LOCK TABLES `SummitSubmissionInvitation` WRITE;
/*!40000 ALTER TABLE `SummitSubmissionInvitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSubmissionInvitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitSubmissionInvitation_Tags`
--

DROP TABLE IF EXISTS `SummitSubmissionInvitation_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitSubmissionInvitation_Tags` (
  `ID` bigint unsigned NOT NULL AUTO_INCREMENT,
  `Created` datetime NOT NULL,
  `LastEdited` datetime NOT NULL,
  `SummitSubmissionInvitationID` int unsigned DEFAULT NULL,
  `TagID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_82057484CA66B12C95B9A210` (`SummitSubmissionInvitationID`,`TagID`),
  KEY `SummitSubmissionInvitationID` (`SummitSubmissionInvitationID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_SummitSubmissionInvitation_Tags_Invitation` FOREIGN KEY (`SummitSubmissionInvitationID`) REFERENCES `SummitSubmissionInvitation` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SummitSubmissionInvitation_Tags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitSubmissionInvitation_Tags`
--

LOCK TABLES `SummitSubmissionInvitation_Tags` WRITE;
/*!40000 ALTER TABLE `SummitSubmissionInvitation_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitSubmissionInvitation_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitTaxType`
--

DROP TABLE IF EXISTS `SummitTaxType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitTaxType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitTaxType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitTaxType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `TaxID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Rate` decimal(9,2) NOT NULL DEFAULT '0.00',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitTaxType`
--

LOCK TABLES `SummitTaxType` WRITE;
/*!40000 ALTER TABLE `SummitTaxType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitTaxType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitTicketType`
--

DROP TABLE IF EXISTS `SummitTicketType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitTicketType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitTicketType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitTicketType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `ExternalId` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  `Cost` decimal(19,4) NOT NULL DEFAULT '0.0000',
  `Currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `QuantityToSell` int NOT NULL DEFAULT '0',
  `QuantitySold` int NOT NULL DEFAULT '0',
  `MaxQuantityToSellPerOrder` int NOT NULL DEFAULT '0',
  `SaleStartDate` datetime DEFAULT NULL,
  `SaleEndDate` datetime DEFAULT NULL,
  `BadgeTypeID` int DEFAULT NULL,
  `Audience` enum('All','WithInvitation','WithoutInvitation') COLLATE utf8mb4_0900_as_cs NOT NULL DEFAULT 'All',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`),
  KEY `BadgeTypeID` (`BadgeTypeID`),
  KEY `Summit_ExternalId` (`SummitID`,`ExternalId`),
  CONSTRAINT `FK_SummitTicketType_Summitt` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=104 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitTicketType`
--

LOCK TABLES `SummitTicketType` WRITE;
/*!40000 ALTER TABLE `SummitTicketType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitTicketType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitTicketType_Taxes`
--

DROP TABLE IF EXISTS `SummitTicketType_Taxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitTicketType_Taxes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitTicketTypeID` int NOT NULL DEFAULT '0',
  `SummitTaxTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitTicketTypeID` (`SummitTicketTypeID`),
  KEY `SummitTaxTypeID` (`SummitTaxTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitTicketType_Taxes`
--

LOCK TABLES `SummitTicketType_Taxes` WRITE;
/*!40000 ALTER TABLE `SummitTicketType_Taxes` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitTicketType_Taxes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitTrackChair`
--

DROP TABLE IF EXISTS `SummitTrackChair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitTrackChair` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitTrackChair') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitTrackChair',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitTrackChair_Member_Summit` (`MemberID`,`SummitID`),
  KEY `MemberID` (`MemberID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=385 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitTrackChair`
--

LOCK TABLES `SummitTrackChair` WRITE;
/*!40000 ALTER TABLE `SummitTrackChair` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitTrackChair` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitTrackChair_Categories`
--

DROP TABLE IF EXISTS `SummitTrackChair_Categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitTrackChair_Categories` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitTrackChairID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitTrackChair_Categories_TrackChairID_CategoryID` (`SummitTrackChairID`,`PresentationCategoryID`),
  KEY `SummitTrackChairID` (`SummitTrackChairID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB AUTO_INCREMENT=1125 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitTrackChair_Categories`
--

LOCK TABLES `SummitTrackChair_Categories` WRITE;
/*!40000 ALTER TABLE `SummitTrackChair_Categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitTrackChair_Categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitType`
--

DROP TABLE IF EXISTS `SummitType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FriendlyName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Audience` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Color` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitType`
--

LOCK TABLES `SummitType` WRITE;
/*!40000 ALTER TABLE `SummitType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitUpdate`
--

DROP TABLE IF EXISTS `SummitUpdate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitUpdate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitUpdate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitUpdate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Category` enum('News','Speakers','Sponsors','Attendees') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'News',
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SummitUpdatesPageID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitUpdatesPageID` (`SummitUpdatesPageID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitUpdate`
--

LOCK TABLES `SummitUpdate` WRITE;
/*!40000 ALTER TABLE `SummitUpdate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitUpdate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitVenue`
--

DROP TABLE IF EXISTS `SummitVenue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitVenue` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsMain` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  CONSTRAINT `FK_6496127911D3633A` FOREIGN KEY (`ID`) REFERENCES `SummitAbstractLocation` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=414 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitVenue`
--

LOCK TABLES `SummitVenue` WRITE;
/*!40000 ALTER TABLE `SummitVenue` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitVenue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitVenueFloor`
--

DROP TABLE IF EXISTS `SummitVenueFloor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitVenueFloor` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitVenueFloor') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitVenueFloor',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Number` int NOT NULL DEFAULT '0',
  `VenueID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VenueID` (`VenueID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitVenueFloor`
--

LOCK TABLES `SummitVenueFloor` WRITE;
/*!40000 ALTER TABLE `SummitVenueFloor` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitVenueFloor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitVenueRoom`
--

DROP TABLE IF EXISTS `SummitVenueRoom`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitVenueRoom` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Capacity` int NOT NULL DEFAULT '0',
  `OverrideBlackouts` tinyint unsigned NOT NULL DEFAULT '0',
  `VenueID` int DEFAULT NULL,
  `FloorID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `VenueID` (`VenueID`),
  KEY `FloorID` (`FloorID`),
  KEY `ImageID` (`ImageID`),
  CONSTRAINT `FK_SummitVenueRoomSummitAbstractLocation` FOREIGN KEY (`ID`) REFERENCES `SummitAbstractLocation` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=422 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitVenueRoom`
--

LOCK TABLES `SummitVenueRoom` WRITE;
/*!40000 ALTER TABLE `SummitVenueRoom` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitVenueRoom` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SummitWIFIConnection`
--

DROP TABLE IF EXISTS `SummitWIFIConnection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SummitWIFIConnection` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SummitWIFIConnection') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SummitWIFIConnection',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `SSID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Password` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SummitWIFIConnection`
--

LOCK TABLES `SummitWIFIConnection` WRITE;
/*!40000 ALTER TABLE `SummitWIFIConnection` DISABLE KEYS */;
/*!40000 ALTER TABLE `SummitWIFIConnection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_ExcludedCategoriesForAcceptedPresentations`
--

DROP TABLE IF EXISTS `Summit_ExcludedCategoriesForAcceptedPresentations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_ExcludedCategoriesForAcceptedPresentations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_ExcludedCategoriesForAcceptedPresentations`
--

LOCK TABLES `Summit_ExcludedCategoriesForAcceptedPresentations` WRITE;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForAcceptedPresentations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForAcceptedPresentations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_ExcludedCategoriesForAlternatePresentations`
--

DROP TABLE IF EXISTS `Summit_ExcludedCategoriesForAlternatePresentations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_ExcludedCategoriesForAlternatePresentations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_ExcludedCategoriesForAlternatePresentations`
--

LOCK TABLES `Summit_ExcludedCategoriesForAlternatePresentations` WRITE;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForAlternatePresentations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForAlternatePresentations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_ExcludedCategoriesForRejectedPresentations`
--

DROP TABLE IF EXISTS `Summit_ExcludedCategoriesForRejectedPresentations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_ExcludedCategoriesForRejectedPresentations` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_ExcludedCategoriesForRejectedPresentations`
--

LOCK TABLES `Summit_ExcludedCategoriesForRejectedPresentations` WRITE;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForRejectedPresentations` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_ExcludedCategoriesForRejectedPresentations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_ExcludedTracksForUploadPresentationSlideDeck`
--

DROP TABLE IF EXISTS `Summit_ExcludedTracksForUploadPresentationSlideDeck`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_ExcludedTracksForUploadPresentationSlideDeck` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationCategoryID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationCategoryID` (`PresentationCategoryID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_ExcludedTracksForUploadPresentationSlideDeck`
--

LOCK TABLES `Summit_ExcludedTracksForUploadPresentationSlideDeck` WRITE;
/*!40000 ALTER TABLE `Summit_ExcludedTracksForUploadPresentationSlideDeck` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_ExcludedTracksForUploadPresentationSlideDeck` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_FeaturedSpeakers`
--

DROP TABLE IF EXISTS `Summit_FeaturedSpeakers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_FeaturedSpeakers` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int DEFAULT NULL,
  `PresentationSpeakerID` int DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_FFDEADE990CF727855E7310E` (`SummitID`,`PresentationSpeakerID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationSpeakerID` (`PresentationSpeakerID`)
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_FeaturedSpeakers`
--

LOCK TABLES `Summit_FeaturedSpeakers` WRITE;
/*!40000 ALTER TABLE `Summit_FeaturedSpeakers` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_FeaturedSpeakers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_PublishedPresentationTypes`
--

DROP TABLE IF EXISTS `Summit_PublishedPresentationTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_PublishedPresentationTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationTypeID` (`PresentationTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_PublishedPresentationTypes`
--

LOCK TABLES `Summit_PublishedPresentationTypes` WRITE;
/*!40000 ALTER TABLE `Summit_PublishedPresentationTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_PublishedPresentationTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_RegistrationCompanies`
--

DROP TABLE IF EXISTS `Summit_RegistrationCompanies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_RegistrationCompanies` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int DEFAULT NULL,
  `CompanyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SummitID_CompanyID` (`SummitID`,`CompanyID`),
  KEY `SummitID` (`SummitID`),
  KEY `CompanyID` (`CompanyID`),
  CONSTRAINT `FK_RegistrationCompanies_Company` FOREIGN KEY (`CompanyID`) REFERENCES `Company` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_RegistrationCompanies_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7852 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_RegistrationCompanies`
--

LOCK TABLES `Summit_RegistrationCompanies` WRITE;
/*!40000 ALTER TABLE `Summit_RegistrationCompanies` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_RegistrationCompanies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_RegularPresentationTypes`
--

DROP TABLE IF EXISTS `Summit_RegularPresentationTypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_RegularPresentationTypes` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SummitID` int NOT NULL DEFAULT '0',
  `PresentationTypeID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `PresentationTypeID` (`PresentationTypeID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_RegularPresentationTypes`
--

LOCK TABLES `Summit_RegularPresentationTypes` WRITE;
/*!40000 ALTER TABLE `Summit_RegularPresentationTypes` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_RegularPresentationTypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Summit_SponsorshipType`
--

DROP TABLE IF EXISTS `Summit_SponsorshipType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Summit_SponsorshipType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `WidgetTitle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `LobbyTemplate` enum('big-images','small-images','horizontal-images','carousel') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `ExpoHallTemplate` enum('big-images','medium-images','small-images') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SponsorPageTemplate` enum('big-header','small-header') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EventPageTemplate` enum('big-images','horizontal-images','small-images') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `SponsorPageShouldUseDisqusWidget` tinyint(1) NOT NULL DEFAULT '1',
  `SponsorPageShouldUseLiveEventWidget` tinyint(1) NOT NULL DEFAULT '1',
  `SponsorPageShouldUseScheduleWidget` tinyint(1) NOT NULL DEFAULT '1',
  `SponsorPageShouldUseBannerWidget` tinyint(1) NOT NULL DEFAULT '1',
  `BadgeImageAltText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `CustomOrder` smallint unsigned NOT NULL DEFAULT '1',
  `BadgeImageID` int DEFAULT NULL,
  `SponsorshipTypeID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `ShouldDisplayOnExpoHallPage` tinyint(1) NOT NULL DEFAULT '1',
  `ShouldDisplayOnLobbyPage` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UNIQ_9926E2A26ABFD72E90CF7278` (`SponsorshipTypeID`,`SummitID`),
  KEY `BadgeImageID` (`BadgeImageID`),
  KEY `SponsorshipTypeID` (`SponsorshipTypeID`),
  KEY `SummitID` (`SummitID`),
  CONSTRAINT `FK_SponsorshipType_Badge_Image` FOREIGN KEY (`BadgeImageID`) REFERENCES `File` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SponsorshipType_Sponsorship` FOREIGN KEY (`SponsorshipTypeID`) REFERENCES `SponsorshipType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_SponsorshipType_Summit` FOREIGN KEY (`SummitID`) REFERENCES `Summit` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2048 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Summit_SponsorshipType`
--

LOCK TABLES `Summit_SponsorshipType` WRITE;
/*!40000 ALTER TABLE `Summit_SponsorshipType` DISABLE KEYS */;
/*!40000 ALTER TABLE `Summit_SponsorshipType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SupportChannelType`
--

DROP TABLE IF EXISTS `SupportChannelType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SupportChannelType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SupportChannelType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SupportChannelType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IconID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Type` (`Type`),
  KEY `IconID` (`IconID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SupportChannelType`
--

LOCK TABLES `SupportChannelType` WRITE;
/*!40000 ALTER TABLE `SupportChannelType` DISABLE KEYS */;
/*!40000 ALTER TABLE `SupportChannelType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SupportingCompany`
--

DROP TABLE IF EXISTS `SupportingCompany`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SupportingCompany` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CustomOrder` int NOT NULL DEFAULT '1',
  `CompanyID` int DEFAULT NULL,
  `ProjectSponsorshipTypeID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ProjectSponsorshipTypeID` (`ProjectSponsorshipTypeID`),
  CONSTRAINT `FK_487453A4802D9F89` FOREIGN KEY (`ProjectSponsorshipTypeID`) REFERENCES `ProjectSponsorshipType` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `FK_487453A49D1F4548` FOREIGN KEY (`CompanyID`) REFERENCES `Company` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SupportingCompany`
--

LOCK TABLES `SupportingCompany` WRITE;
/*!40000 ALTER TABLE `SupportingCompany` DISABLE KEYS */;
/*!40000 ALTER TABLE `SupportingCompany` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Survey`
--

DROP TABLE IF EXISTS `Survey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Survey` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Survey','EntitySurvey') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Survey',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `BeenEmailed` tinyint unsigned NOT NULL DEFAULT '0',
  `IsTest` tinyint unsigned NOT NULL DEFAULT '0',
  `State` enum('INCOMPLETE','SAVED','COMPLETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'INCOMPLETE',
  `Lang` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsMigrated` tinyint unsigned NOT NULL DEFAULT '0',
  `TemplateID` int DEFAULT NULL,
  `CreatedByID` int DEFAULT NULL,
  `CurrentStepID` int DEFAULT NULL,
  `MaxAllowedStepID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TemplateID` (`TemplateID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `CurrentStepID` (`CurrentStepID`),
  KEY `MaxAllowedStepID` (`MaxAllowedStepID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Survey`
--

LOCK TABLES `Survey` WRITE;
/*!40000 ALTER TABLE `Survey` DISABLE KEYS */;
/*!40000 ALTER TABLE `Survey` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyAnswer`
--

DROP TABLE IF EXISTS `SurveyAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyAnswer') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyAnswer',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `QuestionID` int DEFAULT NULL,
  `StepID` int DEFAULT NULL,
  `UpdatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `StepID` (`StepID`),
  KEY `UpdatedByID` (`UpdatedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyAnswer`
--

LOCK TABLES `SurveyAnswer` WRITE;
/*!40000 ALTER TABLE `SurveyAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyAnswerLog`
--

DROP TABLE IF EXISTS `SurveyAnswerLog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyAnswerLog` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyAnswerLog') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyAnswerLog',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `FormerValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `NewValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Operation` enum('INSERT','UPDATE','DELETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'INSERT',
  `QuestionID` int DEFAULT NULL,
  `StepID` int DEFAULT NULL,
  `SurveyID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `StepID` (`StepID`),
  KEY `SurveyID` (`SurveyID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyAnswerLog`
--

LOCK TABLES `SurveyAnswerLog` WRITE;
/*!40000 ALTER TABLE `SurveyAnswerLog` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyAnswerLog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyAnswerTag`
--

DROP TABLE IF EXISTS `SurveyAnswerTag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyAnswerTag` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyAnswerTag') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyAnswerTag',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` enum('AUTOMATIC','CUSTOM','REGEX') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'AUTOMATIC',
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyAnswerTag`
--

LOCK TABLES `SurveyAnswerTag` WRITE;
/*!40000 ALTER TABLE `SurveyAnswerTag` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyAnswerTag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyAnswer_Tags`
--

DROP TABLE IF EXISTS `SurveyAnswer_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyAnswer_Tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SurveyAnswerID` int NOT NULL DEFAULT '0',
  `SurveyAnswerTagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SurveyAnswerID` (`SurveyAnswerID`),
  KEY `SurveyAnswerTagID` (`SurveyAnswerTagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyAnswer_Tags`
--

LOCK TABLES `SurveyAnswer_Tags` WRITE;
/*!40000 ALTER TABLE `SurveyAnswer_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyAnswer_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyCustomValidationRule`
--

DROP TABLE IF EXISTS `SurveyCustomValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyCustomValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `CustomJSMethod` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyCustomValidationRule`
--

LOCK TABLES `SurveyCustomValidationRule` WRITE;
/*!40000 ALTER TABLE `SurveyCustomValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyCustomValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyDoubleEntryTableQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyDoubleEntryTableQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyDoubleEntryTableQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RowsLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AdditionalRowsLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `AdditionalRowsDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyDoubleEntryTableQuestionTemplate`
--

LOCK TABLES `SurveyDoubleEntryTableQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyDoubleEntryTableQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyDoubleEntryTableQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyDropDownQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyDropDownQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyDropDownQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsMultiSelect` tinyint unsigned NOT NULL DEFAULT '0',
  `IsCountrySelector` tinyint unsigned NOT NULL DEFAULT '0',
  `UseCountrySelectorExtraOption` tinyint unsigned NOT NULL DEFAULT '0',
  `UseChosenPlugin` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyDropDownQuestionTemplate`
--

LOCK TABLES `SurveyDropDownQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyDropDownQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyDropDownQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyDynamicEntityStep`
--

DROP TABLE IF EXISTS `SurveyDynamicEntityStep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyDynamicEntityStep` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TemplateID` (`TemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyDynamicEntityStep`
--

LOCK TABLES `SurveyDynamicEntityStep` WRITE;
/*!40000 ALTER TABLE `SurveyDynamicEntityStep` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyDynamicEntityStep` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyDynamicEntityStepTemplate`
--

DROP TABLE IF EXISTS `SurveyDynamicEntityStepTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyDynamicEntityStepTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `AddEntityText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DeleteEntityText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EditEntityText` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EntityIconID` int DEFAULT NULL,
  `EntityID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `EntityIconID` (`EntityIconID`),
  KEY `EntityID` (`EntityID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyDynamicEntityStepTemplate`
--

LOCK TABLES `SurveyDynamicEntityStepTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyDynamicEntityStepTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyDynamicEntityStepTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyLiteralContentQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyLiteralContentQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyLiteralContentQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyLiteralContentQuestionTemplate`
--

LOCK TABLES `SurveyLiteralContentQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyLiteralContentQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyLiteralContentQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyMaxLengthValidationRule`
--

DROP TABLE IF EXISTS `SurveyMaxLengthValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyMaxLengthValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MaxLength` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyMaxLengthValidationRule`
--

LOCK TABLES `SurveyMaxLengthValidationRule` WRITE;
/*!40000 ALTER TABLE `SurveyMaxLengthValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyMaxLengthValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyMinLengthValidationRule`
--

DROP TABLE IF EXISTS `SurveyMinLengthValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyMinLengthValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MinLength` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyMinLengthValidationRule`
--

LOCK TABLES `SurveyMinLengthValidationRule` WRITE;
/*!40000 ALTER TABLE `SurveyMinLengthValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyMinLengthValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyMultiValueQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyMultiValueQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyMultiValueQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EmptyString` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DefaultGroupLabel` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DefaultValueID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DefaultValueID` (`DefaultValueID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyMultiValueQuestionTemplate`
--

LOCK TABLES `SurveyMultiValueQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyMultiValueQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyMultiValueQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyPage`
--

DROP TABLE IF EXISTS `SurveyPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SurveyTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SurveyTemplateID` (`SurveyTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyPage`
--

LOCK TABLES `SurveyPage` WRITE;
/*!40000 ALTER TABLE `SurveyPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyPage_Live`
--

DROP TABLE IF EXISTS `SurveyPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SurveyTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SurveyTemplateID` (`SurveyTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyPage_Live`
--

LOCK TABLES `SurveyPage_Live` WRITE;
/*!40000 ALTER TABLE `SurveyPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyPage_versions`
--

DROP TABLE IF EXISTS `SurveyPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `ThankYouText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SurveyTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `SurveyTemplateID` (`SurveyTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyPage_versions`
--

LOCK TABLES `SurveyPage_versions` WRITE;
/*!40000 ALTER TABLE `SurveyPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyQuestionRowValueTemplate`
--

DROP TABLE IF EXISTS `SurveyQuestionRowValueTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyQuestionRowValueTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsAdditional` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyQuestionRowValueTemplate`
--

LOCK TABLES `SurveyQuestionRowValueTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyQuestionRowValueTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyQuestionRowValueTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyQuestionTemplate','SurveyLiteralContentQuestionTemplate','SurveyMultiValueQuestionTemplate','SurveyCheckBoxListQuestionTemplate','SurveyDoubleEntryTableQuestionTemplate','SurveyRadioButtonMatrixTemplateQuestion','SurveyDropDownQuestionTemplate','SurveyRadioButtonListQuestionTemplate','SurveyRankingQuestionTemplate','SurveySingleValueTemplateQuestion','SurveyCheckBoxQuestionTemplate','SurveyOrganizationQuestionTemplate','SurveyTextAreaQuestionTemplate','SurveyTextBoxQuestionTemplate','SurveyEmailQuestionTemplate','SurveyMemberCountryQuestionTemplate','SurveyMemberEmailQuestionTemplate','SurveyMemberFirstNameQuestionTemplate','SurveyMemberLastNameQuestionTemplate','SurveyNumericQuestionTemplate','SurveyPercentageQuestionTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyQuestionTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `Mandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ReadOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowOnSangriaStatistics` tinyint unsigned NOT NULL DEFAULT '0',
  `ShowOnPublicStatistics` tinyint unsigned NOT NULL DEFAULT '0',
  `Hidden` tinyint unsigned NOT NULL DEFAULT '0',
  `StepID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `StepID_Name` (`StepID`,`Name`),
  KEY `StepID` (`StepID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyQuestionTemplate`
--

LOCK TABLES `SurveyQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyQuestionTemplate_DependsOn`
--

DROP TABLE IF EXISTS `SurveyQuestionTemplate_DependsOn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyQuestionTemplate_DependsOn` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SurveyQuestionTemplateID` int NOT NULL DEFAULT '0',
  `ChildID` int NOT NULL DEFAULT '0',
  `ValueID` int NOT NULL DEFAULT '0',
  `Operator` enum('Equal','Not-Equal') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Equal',
  `Visibility` enum('Visible','Not-Visible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Visible',
  `BooleanOperatorOnValues` enum('And','Or') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'And',
  `DefaultValue` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SurveyQuestionTemplateID` (`SurveyQuestionTemplateID`),
  KEY `ChildID` (`ChildID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyQuestionTemplate_DependsOn`
--

LOCK TABLES `SurveyQuestionTemplate_DependsOn` WRITE;
/*!40000 ALTER TABLE `SurveyQuestionTemplate_DependsOn` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyQuestionTemplate_DependsOn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyQuestionValueTemplate`
--

DROP TABLE IF EXISTS `SurveyQuestionValueTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyQuestionValueTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyQuestionValueTemplate','SurveyQuestionColumnValueTemplate','SurveyQuestionRowValueTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyQuestionValueTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OwnerID` int DEFAULT NULL,
  `GroupID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `GroupID` (`GroupID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyQuestionValueTemplate`
--

LOCK TABLES `SurveyQuestionValueTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyQuestionValueTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyQuestionValueTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyQuestionValueTemplateGroup`
--

DROP TABLE IF EXISTS `SurveyQuestionValueTemplateGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyQuestionValueTemplateGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyQuestionValueTemplateGroup') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyQuestionValueTemplateGroup',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyQuestionValueTemplateGroup`
--

LOCK TABLES `SurveyQuestionValueTemplateGroup` WRITE;
/*!40000 ALTER TABLE `SurveyQuestionValueTemplateGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyQuestionValueTemplateGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyRadioButtonListQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyRadioButtonListQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyRadioButtonListQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Orientation` enum('Horizontal','Vertical') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Vertical',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyRadioButtonListQuestionTemplate`
--

LOCK TABLES `SurveyRadioButtonListQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyRadioButtonListQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyRadioButtonListQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyRangeValidationRule`
--

DROP TABLE IF EXISTS `SurveyRangeValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyRangeValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MinRange` int NOT NULL DEFAULT '0',
  `MaxRange` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyRangeValidationRule`
--

LOCK TABLES `SurveyRangeValidationRule` WRITE;
/*!40000 ALTER TABLE `SurveyRangeValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyRangeValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyRankingQuestionTemplate`
--

DROP TABLE IF EXISTS `SurveyRankingQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyRankingQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `MaxItemsToRank` int NOT NULL DEFAULT '0',
  `Intro` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyRankingQuestionTemplate`
--

LOCK TABLES `SurveyRankingQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyRankingQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyRankingQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyReport`
--

DROP TABLE IF EXISTS `SurveyReport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyReport` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyReport') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyReport',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(254) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Display` tinyint unsigned NOT NULL DEFAULT '1',
  `TemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TemplateID` (`TemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyReport`
--

LOCK TABLES `SurveyReport` WRITE;
/*!40000 ALTER TABLE `SurveyReport` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyReport` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyReportFilter`
--

DROP TABLE IF EXISTS `SurveyReportFilter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyReportFilter` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyReportFilter') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyReportFilter',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `QuestionID` int DEFAULT NULL,
  `ReportID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `ReportID` (`ReportID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyReportFilter`
--

LOCK TABLES `SurveyReportFilter` WRITE;
/*!40000 ALTER TABLE `SurveyReportFilter` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyReportFilter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyReportGraph`
--

DROP TABLE IF EXISTS `SurveyReportGraph`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyReportGraph` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyReportGraph') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyReportGraph',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` enum('pie','bars','multibars') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'pie',
  `Order` int NOT NULL DEFAULT '0',
  `QuestionID` int DEFAULT NULL,
  `SectionID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `SectionID` (`SectionID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyReportGraph`
--

LOCK TABLES `SurveyReportGraph` WRITE;
/*!40000 ALTER TABLE `SurveyReportGraph` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyReportGraph` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyReportSection`
--

DROP TABLE IF EXISTS `SurveyReportSection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyReportSection` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyReportSection') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyReportSection',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '0',
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ReportID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ReportID` (`ReportID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyReportSection`
--

LOCK TABLES `SurveyReportSection` WRITE;
/*!40000 ALTER TABLE `SurveyReportSection` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyReportSection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveySingleValueTemplateQuestion`
--

DROP TABLE IF EXISTS `SurveySingleValueTemplateQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveySingleValueTemplateQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InitialValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveySingleValueTemplateQuestion`
--

LOCK TABLES `SurveySingleValueTemplateQuestion` WRITE;
/*!40000 ALTER TABLE `SurveySingleValueTemplateQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveySingleValueTemplateQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveySingleValueTemplateQuestion_ValidationRules`
--

DROP TABLE IF EXISTS `SurveySingleValueTemplateQuestion_ValidationRules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveySingleValueTemplateQuestion_ValidationRules` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SurveySingleValueTemplateQuestionID` int NOT NULL DEFAULT '0',
  `SurveySingleValueValidationRuleID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `SurveySingleValueTemplateQuestionID` (`SurveySingleValueTemplateQuestionID`),
  KEY `SurveySingleValueValidationRuleID` (`SurveySingleValueValidationRuleID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveySingleValueTemplateQuestion_ValidationRules`
--

LOCK TABLES `SurveySingleValueTemplateQuestion_ValidationRules` WRITE;
/*!40000 ALTER TABLE `SurveySingleValueTemplateQuestion_ValidationRules` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveySingleValueTemplateQuestion_ValidationRules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveySingleValueValidationRule`
--

DROP TABLE IF EXISTS `SurveySingleValueValidationRule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveySingleValueValidationRule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveySingleValueValidationRule','SurveyCustomValidationRule','SurveyMaxLengthValidationRule','SurveyMinLengthValidationRule','SurveyNumberValidationRule','SurveyRangeValidationRule') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveySingleValueValidationRule',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Message` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveySingleValueValidationRule`
--

LOCK TABLES `SurveySingleValueValidationRule` WRITE;
/*!40000 ALTER TABLE `SurveySingleValueValidationRule` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveySingleValueValidationRule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyStep`
--

DROP TABLE IF EXISTS `SurveyStep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyStep` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyStep','SurveyDynamicEntityStep','SurveyRegularStep') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyStep',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `State` enum('INCOMPLETE','COMPLETE') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'INCOMPLETE',
  `TemplateID` int DEFAULT NULL,
  `SurveyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TemplateID` (`TemplateID`),
  KEY `SurveyID` (`SurveyID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyStep`
--

LOCK TABLES `SurveyStep` WRITE;
/*!40000 ALTER TABLE `SurveyStep` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyStep` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyStepTemplate`
--

DROP TABLE IF EXISTS `SurveyStepTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyStepTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyStepTemplate','SurveyDynamicEntityStepTemplate','SurveyRegularStepTemplate','SurveyThankYouStepTemplate','SurveyReviewStepTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyStepTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FriendlyName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Order` int NOT NULL DEFAULT '0',
  `SkipStep` tinyint unsigned NOT NULL DEFAULT '0',
  `SurveyTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `SurveyTemplateID_Name` (`SurveyTemplateID`,`Name`),
  KEY `SurveyTemplateID` (`SurveyTemplateID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyStepTemplate`
--

LOCK TABLES `SurveyStepTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyStepTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyStepTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyStepTemplate_DependsOn`
--

DROP TABLE IF EXISTS `SurveyStepTemplate_DependsOn`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyStepTemplate_DependsOn` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `SurveyStepTemplateID` int NOT NULL DEFAULT '0',
  `SurveyQuestionTemplateID` int NOT NULL DEFAULT '0',
  `ValueID` int NOT NULL DEFAULT '0',
  `Operator` enum('Equal','Not-Equal') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Equal',
  `Visibility` enum('Visible','Not-Visible') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Visible',
  `BooleanOperatorOnValues` enum('And','Or') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'And',
  PRIMARY KEY (`ID`),
  KEY `SurveyStepTemplateID` (`SurveyStepTemplateID`),
  KEY `SurveyQuestionTemplateID` (`SurveyQuestionTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyStepTemplate_DependsOn`
--

LOCK TABLES `SurveyStepTemplate_DependsOn` WRITE;
/*!40000 ALTER TABLE `SurveyStepTemplate_DependsOn` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyStepTemplate_DependsOn` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyTemplate`
--

DROP TABLE IF EXISTS `SurveyTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('SurveyTemplate','EntitySurveyTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'SurveyTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `Enabled` tinyint unsigned NOT NULL DEFAULT '0',
  `CreatedByID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CreatedByID` (`CreatedByID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyTemplate`
--

LOCK TABLES `SurveyTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `SurveyThankYouStepTemplate`
--

DROP TABLE IF EXISTS `SurveyThankYouStepTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `SurveyThankYouStepTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EmailTemplateID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `EmailTemplateID` (`EmailTemplateID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `SurveyThankYouStepTemplate`
--

LOCK TABLES `SurveyThankYouStepTemplate` WRITE;
/*!40000 ALTER TABLE `SurveyThankYouStepTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `SurveyThankYouStepTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tag`
--

DROP TABLE IF EXISTS `Tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tag` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Tag') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Tag',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Tag` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tag`
--

LOCK TABLES `Tag` WRITE;
/*!40000 ALTER TABLE `Tag` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tag` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Team`
--

DROP TABLE IF EXISTS `Team`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Team` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Team') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Team',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CompanyID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CompanyID` (`CompanyID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Team`
--

LOCK TABLES `Team` WRITE;
/*!40000 ALTER TABLE `Team` DISABLE KEYS */;
/*!40000 ALTER TABLE `Team` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TeamInvitation`
--

DROP TABLE IF EXISTS `TeamInvitation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TeamInvitation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TeamInvitation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TeamInvitation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Email` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `FirstName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LastName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ConfirmationHash` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `IsConfirmed` tinyint unsigned NOT NULL DEFAULT '0',
  `ConfirmationDate` datetime DEFAULT NULL,
  `TeamID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TeamID` (`TeamID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TeamInvitation`
--

LOCK TABLES `TeamInvitation` WRITE;
/*!40000 ALTER TABLE `TeamInvitation` DISABLE KEYS */;
/*!40000 ALTER TABLE `TeamInvitation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Team_Members`
--

DROP TABLE IF EXISTS `Team_Members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Team_Members` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TeamID` int NOT NULL DEFAULT '0',
  `MemberID` int NOT NULL DEFAULT '0',
  `DateAdded` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TeamID` (`TeamID`),
  KEY `MemberID` (`MemberID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Team_Members`
--

LOCK TABLES `Team_Members` WRITE;
/*!40000 ALTER TABLE `Team_Members` DISABLE KEYS */;
/*!40000 ALTER TABLE `Team_Members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Topic`
--

DROP TABLE IF EXISTS `Topic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Topic` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Topic') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Topic',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Topic`
--

LOCK TABLES `Topic` WRITE;
/*!40000 ALTER TABLE `Topic` DISABLE KEYS */;
/*!40000 ALTER TABLE `Topic` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackAnswer`
--

DROP TABLE IF EXISTS `TrackAnswer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackAnswer` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrackAnswer') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrackAnswer',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `QuestionID` int DEFAULT NULL,
  `PresentationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `QuestionID` (`QuestionID`),
  KEY `PresentationID` (`PresentationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackAnswer`
--

LOCK TABLES `TrackAnswer` WRITE;
/*!40000 ALTER TABLE `TrackAnswer` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackAnswer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackCheckBoxListQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackCheckBoxListQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackCheckBoxListQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackCheckBoxListQuestionTemplate`
--

LOCK TABLES `TrackCheckBoxListQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackCheckBoxListQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackCheckBoxListQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackCheckBoxQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackCheckBoxQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackCheckBoxQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackCheckBoxQuestionTemplate`
--

LOCK TABLES `TrackCheckBoxQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackCheckBoxQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackCheckBoxQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackDropDownQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackDropDownQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackDropDownQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `IsMultiSelect` tinyint unsigned NOT NULL DEFAULT '0',
  `IsCountrySelector` tinyint unsigned NOT NULL DEFAULT '0',
  `UseChosenPlugin` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackDropDownQuestionTemplate`
--

LOCK TABLES `TrackDropDownQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackDropDownQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackDropDownQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackLiteralContentQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackLiteralContentQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackLiteralContentQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackLiteralContentQuestionTemplate`
--

LOCK TABLES `TrackLiteralContentQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackLiteralContentQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackLiteralContentQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackMultiValueQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackMultiValueQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackMultiValueQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `EmptyString` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `DefaultValueID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `DefaultValueID` (`DefaultValueID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackMultiValueQuestionTemplate`
--

LOCK TABLES `TrackMultiValueQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackMultiValueQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackMultiValueQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrackQuestionTemplate','TrackLiteralContentQuestionTemplate','TrackMultiValueQuestionTemplate','TrackCheckBoxListQuestionTemplate','TrackDropDownQuestionTemplate','TrackRadioButtonListQuestionTemplate','TrackSingleValueTemplateQuestion','TrackCheckBoxQuestionTemplate','TrackTextBoxQuestionTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrackQuestionTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Mandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `ReadOnly` tinyint unsigned NOT NULL DEFAULT '0',
  `AfterQuestion` enum('Title','CategoryContainer','LevelProblemAddressed','AttendeesExpectedLearnt','Last') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Last',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackQuestionTemplate`
--

LOCK TABLES `TrackQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackQuestionValueTemplate`
--

DROP TABLE IF EXISTS `TrackQuestionValueTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackQuestionValueTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrackQuestionValueTemplate') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrackQuestionValueTemplate',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Label` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `OwnerID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OwnerID` (`OwnerID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackQuestionValueTemplate`
--

LOCK TABLES `TrackQuestionValueTemplate` WRITE;
/*!40000 ALTER TABLE `TrackQuestionValueTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackQuestionValueTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackRadioButtonListQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackRadioButtonListQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackRadioButtonListQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackRadioButtonListQuestionTemplate`
--

LOCK TABLES `TrackRadioButtonListQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackRadioButtonListQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackRadioButtonListQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackSingleValueTemplateQuestion`
--

DROP TABLE IF EXISTS `TrackSingleValueTemplateQuestion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackSingleValueTemplateQuestion` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `InitialValue` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackSingleValueTemplateQuestion`
--

LOCK TABLES `TrackSingleValueTemplateQuestion` WRITE;
/*!40000 ALTER TABLE `TrackSingleValueTemplateQuestion` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackSingleValueTemplateQuestion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackTagGroup`
--

DROP TABLE IF EXISTS `TrackTagGroup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackTagGroup` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrackTagGroup') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrackTagGroup',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Label` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Order` int NOT NULL DEFAULT '1',
  `Mandatory` tinyint unsigned NOT NULL DEFAULT '0',
  `SummitID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `SummitID` (`SummitID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackTagGroup`
--

LOCK TABLES `TrackTagGroup` WRITE;
/*!40000 ALTER TABLE `TrackTagGroup` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackTagGroup` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackTagGroup_AllowedTags`
--

DROP TABLE IF EXISTS `TrackTagGroup_AllowedTags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackTagGroup_AllowedTags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TrackTagGroupID` int NOT NULL DEFAULT '0',
  `TagID` int NOT NULL DEFAULT '0',
  `IsDefault` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `TrackTagGroupID` (`TrackTagGroupID`),
  KEY `TagID` (`TagID`),
  CONSTRAINT `FK_TrackTagGroup_AllowedTags_Tag` FOREIGN KEY (`TagID`) REFERENCES `Tag` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `FK_TrackTagGroup_AllowedTags_TrackTagGroupID` FOREIGN KEY (`TrackTagGroupID`) REFERENCES `TrackTagGroup` (`ID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackTagGroup_AllowedTags`
--

LOCK TABLES `TrackTagGroup_AllowedTags` WRITE;
/*!40000 ALTER TABLE `TrackTagGroup_AllowedTags` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackTagGroup_AllowedTags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrackTextBoxQuestionTemplate`
--

DROP TABLE IF EXISTS `TrackTextBoxQuestionTemplate`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrackTextBoxQuestionTemplate` (
  `ID` int NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrackTextBoxQuestionTemplate`
--

LOCK TABLES `TrackTextBoxQuestionTemplate` WRITE;
/*!40000 ALTER TABLE `TrackTextBoxQuestionTemplate` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrackTextBoxQuestionTemplate` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingActivity`
--

DROP TABLE IF EXISTS `TrainingActivity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingActivity` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingActivity') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingActivity',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Title` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingActivity`
--

LOCK TABLES `TrainingActivity` WRITE;
/*!40000 ALTER TABLE `TrainingActivity` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingActivity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourse`
--

DROP TABLE IF EXISTS `TrainingCourse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourse` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCourse') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCourse',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Paid` tinyint unsigned NOT NULL DEFAULT '0',
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Online` tinyint unsigned NOT NULL DEFAULT '0',
  `TrainingServiceID` int DEFAULT NULL,
  `TypeID` int DEFAULT NULL,
  `LevelID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TrainingServiceID` (`TrainingServiceID`),
  KEY `TypeID` (`TypeID`),
  KEY `LevelID` (`LevelID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourse`
--

LOCK TABLES `TrainingCourse` WRITE;
/*!40000 ALTER TABLE `TrainingCourse` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourse` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourseLevel`
--

DROP TABLE IF EXISTS `TrainingCourseLevel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourseLevel` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCourseLevel') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCourseLevel',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Level` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourseLevel`
--

LOCK TABLES `TrainingCourseLevel` WRITE;
/*!40000 ALTER TABLE `TrainingCourseLevel` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourseLevel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCoursePrerequisite`
--

DROP TABLE IF EXISTS `TrainingCoursePrerequisite`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCoursePrerequisite` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCoursePrerequisite') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCoursePrerequisite',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCoursePrerequisite`
--

LOCK TABLES `TrainingCoursePrerequisite` WRITE;
/*!40000 ALTER TABLE `TrainingCoursePrerequisite` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCoursePrerequisite` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourseSchedule`
--

DROP TABLE IF EXISTS `TrainingCourseSchedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourseSchedule` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCourseSchedule') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCourseSchedule',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `City` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `State` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Country` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `CourseID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CourseID` (`CourseID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourseSchedule`
--

LOCK TABLES `TrainingCourseSchedule` WRITE;
/*!40000 ALTER TABLE `TrainingCourseSchedule` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourseSchedule` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourseScheduleTime`
--

DROP TABLE IF EXISTS `TrainingCourseScheduleTime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourseScheduleTime` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCourseScheduleTime') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCourseScheduleTime',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LocationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `LocationID` (`LocationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourseScheduleTime`
--

LOCK TABLES `TrainingCourseScheduleTime` WRITE;
/*!40000 ALTER TABLE `TrainingCourseScheduleTime` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourseScheduleTime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourseType`
--

DROP TABLE IF EXISTS `TrainingCourseType`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourseType` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('TrainingCourseType') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'TrainingCourseType',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourseType`
--

LOCK TABLES `TrainingCourseType` WRITE;
/*!40000 ALTER TABLE `TrainingCourseType` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourseType` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourse_Prerequisites`
--

DROP TABLE IF EXISTS `TrainingCourse_Prerequisites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourse_Prerequisites` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TrainingCourseID` int NOT NULL DEFAULT '0',
  `TrainingCoursePrerequisiteID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `TrainingCourseID` (`TrainingCourseID`),
  KEY `TrainingCoursePrerequisiteID` (`TrainingCoursePrerequisiteID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourse_Prerequisites`
--

LOCK TABLES `TrainingCourse_Prerequisites` WRITE;
/*!40000 ALTER TABLE `TrainingCourse_Prerequisites` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourse_Prerequisites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingCourse_Projects`
--

DROP TABLE IF EXISTS `TrainingCourse_Projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingCourse_Projects` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `TrainingCourseID` int NOT NULL DEFAULT '0',
  `ProjectID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `TrainingCourseID` (`TrainingCourseID`),
  KEY `ProjectID` (`ProjectID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingCourse_Projects`
--

LOCK TABLES `TrainingCourse_Projects` WRITE;
/*!40000 ALTER TABLE `TrainingCourse_Projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingCourse_Projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TrainingService`
--

DROP TABLE IF EXISTS `TrainingService`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `TrainingService` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `Priority` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TrainingService`
--

LOCK TABLES `TrainingService` WRITE;
/*!40000 ALTER TABLE `TrainingService` DISABLE KEYS */;
/*!40000 ALTER TABLE `TrainingService` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoriesIndustry`
--

DROP TABLE IF EXISTS `UserStoriesIndustry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoriesIndustry` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('UserStoriesIndustry') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'UserStoriesIndustry',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `IndustryName` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Active` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoriesIndustry`
--

LOCK TABLES `UserStoriesIndustry` WRITE;
/*!40000 ALTER TABLE `UserStoriesIndustry` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoriesIndustry` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoriesPage`
--

DROP TABLE IF EXISTS `UserStoriesPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoriesPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoriesPage`
--

LOCK TABLES `UserStoriesPage` WRITE;
/*!40000 ALTER TABLE `UserStoriesPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoriesPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoriesPage_Live`
--

DROP TABLE IF EXISTS `UserStoriesPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoriesPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoriesPage_Live`
--

LOCK TABLES `UserStoriesPage_Live` WRITE;
/*!40000 ALTER TABLE `UserStoriesPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoriesPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoriesPage_versions`
--

DROP TABLE IF EXISTS `UserStoriesPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoriesPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `HeaderText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `HeroText` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HeroImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `HeroImageID` (`HeroImageID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoriesPage_versions`
--

LOCK TABLES `UserStoriesPage_versions` WRITE;
/*!40000 ALTER TABLE `UserStoriesPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoriesPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoryDO`
--

DROP TABLE IF EXISTS `UserStoryDO`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoryDO` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('UserStoryDO') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'UserStoryDO',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `ShortDescription` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Link` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Active` tinyint unsigned NOT NULL DEFAULT '1',
  `IndustryID` int DEFAULT NULL,
  `OrganizationID` int DEFAULT NULL,
  `LocationID` int DEFAULT NULL,
  `ImageID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `IndustryID` (`IndustryID`),
  KEY `OrganizationID` (`OrganizationID`),
  KEY `LocationID` (`LocationID`),
  KEY `ImageID` (`ImageID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoryDO`
--

LOCK TABLES `UserStoryDO` WRITE;
/*!40000 ALTER TABLE `UserStoryDO` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoryDO` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserStoryDO_Tags`
--

DROP TABLE IF EXISTS `UserStoryDO_Tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserStoryDO_Tags` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `UserStoryDOID` int NOT NULL DEFAULT '0',
  `TagID` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `UserStoryDOID` (`UserStoryDOID`),
  KEY `TagID` (`TagID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserStoryDO_Tags`
--

LOCK TABLES `UserStoryDO_Tags` WRITE;
/*!40000 ALTER TABLE `UserStoryDO_Tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserStoryDO_Tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserSurveyPage`
--

DROP TABLE IF EXISTS `UserSurveyPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserSurveyPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LoginPageTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide1Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide2Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide3Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserSurveyPage`
--

LOCK TABLES `UserSurveyPage` WRITE;
/*!40000 ALTER TABLE `UserSurveyPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserSurveyPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserSurveyPage_Live`
--

DROP TABLE IF EXISTS `UserSurveyPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserSurveyPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `LoginPageTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide1Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide2Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide3Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserSurveyPage_Live`
--

LOCK TABLES `UserSurveyPage_Live` WRITE;
/*!40000 ALTER TABLE `UserSurveyPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserSurveyPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `UserSurveyPage_versions`
--

DROP TABLE IF EXISTS `UserSurveyPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `UserSurveyPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `LoginPageTitle` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageContent` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide1Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide2Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `LoginPageSlide3Content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `UserSurveyPage_versions`
--

LOCK TABLES `UserSurveyPage_versions` WRITE;
/*!40000 ALTER TABLE `UserSurveyPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `UserSurveyPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VideoLink`
--

DROP TABLE IF EXISTS `VideoLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VideoLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('VideoLink','MarketingVideo','OpenStackDaysVideo') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'VideoLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `YoutubeID` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Caption` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SortOrder` int NOT NULL DEFAULT '0',
  `ThumbnailID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `ThumbnailID` (`ThumbnailID`),
  KEY `SortOrder` (`SortOrder`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VideoLink`
--

LOCK TABLES `VideoLink` WRITE;
/*!40000 ALTER TABLE `VideoLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `VideoLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VideoPresentation`
--

DROP TABLE IF EXISTS `VideoPresentation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VideoPresentation` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('VideoPresentation') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'VideoPresentation',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Name` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `DisplayOnSite` tinyint unsigned NOT NULL DEFAULT '0',
  `Featured` tinyint unsigned NOT NULL DEFAULT '0',
  `City` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `YouTubeID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `URLSegment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `StartTime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `EndTime` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `Location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Type` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `Day` int NOT NULL DEFAULT '0',
  `Speakers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `SlidesLink` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `event_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `IsKeynote` tinyint unsigned NOT NULL DEFAULT '0',
  `SchedID` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `HostedMediaURL` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs,
  `MediaType` enum('URL','File') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'URL',
  `PresentationCategoryPageID` int DEFAULT NULL,
  `SummitID` int DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  `UploadedMediaID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `PresentationCategoryPageID` (`PresentationCategoryPageID`),
  KEY `SummitID` (`SummitID`),
  KEY `MemberID` (`MemberID`),
  KEY `UploadedMediaID` (`UploadedMediaID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VideoPresentation`
--

LOCK TABLES `VideoPresentation` WRITE;
/*!40000 ALTER TABLE `VideoPresentation` DISABLE KEYS */;
/*!40000 ALTER TABLE `VideoPresentation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VirtualPage`
--

DROP TABLE IF EXISTS `VirtualPage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VirtualPage` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `VersionID` int NOT NULL DEFAULT '0',
  `CopyContentFromID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CopyContentFromID` (`CopyContentFromID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VirtualPage`
--

LOCK TABLES `VirtualPage` WRITE;
/*!40000 ALTER TABLE `VirtualPage` DISABLE KEYS */;
/*!40000 ALTER TABLE `VirtualPage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VirtualPage_Live`
--

DROP TABLE IF EXISTS `VirtualPage_Live`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VirtualPage_Live` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `VersionID` int NOT NULL DEFAULT '0',
  `CopyContentFromID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `CopyContentFromID` (`CopyContentFromID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VirtualPage_Live`
--

LOCK TABLES `VirtualPage_Live` WRITE;
/*!40000 ALTER TABLE `VirtualPage_Live` DISABLE KEYS */;
/*!40000 ALTER TABLE `VirtualPage_Live` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `VirtualPage_versions`
--

DROP TABLE IF EXISTS `VirtualPage_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `VirtualPage_versions` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `RecordID` int NOT NULL DEFAULT '0',
  `Version` int NOT NULL DEFAULT '0',
  `VersionID` int NOT NULL DEFAULT '0',
  `CopyContentFromID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `RecordID_Version` (`RecordID`,`Version`),
  KEY `RecordID` (`RecordID`),
  KEY `Version` (`Version`),
  KEY `CopyContentFromID` (`CopyContentFromID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `VirtualPage_versions`
--

LOCK TABLES `VirtualPage_versions` WRITE;
/*!40000 ALTER TABLE `VirtualPage_versions` DISABLE KEYS */;
/*!40000 ALTER TABLE `VirtualPage_versions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Voter`
--

DROP TABLE IF EXISTS `Voter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Voter` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('Voter') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'Voter',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `MemberID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `MemberID` (`MemberID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Voter`
--

LOCK TABLES `Voter` WRITE;
/*!40000 ALTER TABLE `Voter` DISABLE KEYS */;
/*!40000 ALTER TABLE `Voter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ZenDeskLink`
--

DROP TABLE IF EXISTS `ZenDeskLink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ZenDeskLink` (
  `ID` int NOT NULL AUTO_INCREMENT,
  `ClassName` enum('ZenDeskLink') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT 'ZenDeskLink',
  `LastEdited` datetime DEFAULT NULL,
  `Created` datetime DEFAULT NULL,
  `Link` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_as_cs DEFAULT NULL,
  `OpenStackImplementationID` int DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `OpenStackImplementationID` (`OpenStackImplementationID`),
  KEY `ClassName` (`ClassName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ZenDeskLink`
--

LOCK TABLES `ZenDeskLink` WRITE;
/*!40000 ALTER TABLE `ZenDeskLink` DISABLE KEYS */;
/*!40000 ALTER TABLE `ZenDeskLink` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oid_associations`
--

DROP TABLE IF EXISTS `oid_associations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oid_associations` (
  `server_url` varchar(2047) COLLATE utf8mb4_0900_as_cs NOT NULL,
  `handle` varchar(255) COLLATE utf8mb4_0900_as_cs NOT NULL,
  `secret` blob NOT NULL,
  `issued` int NOT NULL,
  `lifetime` int NOT NULL,
  `assoc_type` varchar(64) COLLATE utf8mb4_0900_as_cs NOT NULL,
  PRIMARY KEY (`server_url`(255),`handle`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oid_associations`
--

LOCK TABLES `oid_associations` WRITE;
/*!40000 ALTER TABLE `oid_associations` DISABLE KEYS */;
/*!40000 ALTER TABLE `oid_associations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oid_nonces`
--

DROP TABLE IF EXISTS `oid_nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `oid_nonces` (
  `server_url` varchar(2047) COLLATE utf8mb4_0900_as_cs NOT NULL,
  `timestamp` int NOT NULL,
  `salt` char(40) COLLATE utf8mb4_0900_as_cs NOT NULL,
  UNIQUE KEY `server_url` (`server_url`(255),`timestamp`,`salt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_as_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oid_nonces`
--

LOCK TABLES `oid_nonces` WRITE;
/*!40000 ALTER TABLE `oid_nonces` DISABLE KEYS */;
/*!40000 ALTER TABLE `oid_nonces` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40111 SET SQL_REQUIRE_PRIMARY_KEY=@OLD_SQL_REQUIRE_PRIMARY_KEY */;

-- Dump completed on 2023-03-31 14:07:27
