-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: ateye
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `delivery`
--

DROP TABLE IF EXISTS `delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery` (
  `delivery_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `delivery_person_id` int NOT NULL,
  `status` enum('Assigned','Picked Up','On the Way','Delivered') DEFAULT 'Assigned',
  PRIMARY KEY (`delivery_id`),
  KEY `order_id` (`order_id`),
  KEY `delivery_person_id` (`delivery_person_id`),
  CONSTRAINT `delivery_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `delivery_ibfk_2` FOREIGN KEY (`delivery_person_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery`
--

LOCK TABLES `delivery` WRITE;
/*!40000 ALTER TABLE `delivery` DISABLE KEYS */;
INSERT INTO `delivery` VALUES (1,1,5,'Delivered'),(2,2,8,'Delivered'),(3,3,8,'Delivered'),(4,5,7,'Delivered'),(5,4,11,'Delivered'),(6,7,8,'Delivered'),(7,6,7,'Assigned'),(8,9,8,'Picked Up');
/*!40000 ALTER TABLE `delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menuitem`
--

DROP TABLE IF EXISTS `menuitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `menuitem` (
  `item_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT '1',
  `category` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menuitem`
--

LOCK TABLES `menuitem` WRITE;
/*!40000 ALTER TABLE `menuitem` DISABLE KEYS */;
INSERT INTO `menuitem` VALUES (1,'waraaqo','tttt',30000.00,'https://www.101cookbooks.com/homemade-pasta/',0,NULL),(2,'pasta','dfdfdd',3.00,'',0,''),(3,'rice','dafkdasd',3.00,'',0,NULL),(4,'pasta','for somali',1.00,'https://imgs.search.brave.com/IK9C9bww1QdDrBEkduuFHxykWzH9Dgq6zQBHeWdKEEk/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly9pLnBp/bmltZy5jb20vb3Jp/Z2luYWxzLzlmL2Yz/LzZkLzlmZjM2ZDIw/OGQwYzg1NTlmYTc3/NzM1ZDAxOTk0Mzlk/LmpwZw',1,''),(5,'rice','for',2.00,'https://imgs.search.brave.com/qd6e0pFo68ujdvkxwpLCz2UQfsAT5zQVqAKy-Fc0h9I/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly9pbWcu/ZnJlZXBpay5jb20v/cHJlbWl1bS1waG90/by93aGl0ZS1yaWNl/LWNvb2tlZC13aXRo/LWdhcm5pc2hlc181/Mzg2NDYtNzcuanBn/P3NlbXQ9YWlzX2h5/YnJpZCZ3PTc0MCZx/PTgw',1,''),(6,'chicken','chickens',4.00,'https://imgs.search.brave.com/Ei_cxFbqAxtU6haAwHaU9xP0gHGyJyf8wS3wdGs4ABI/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly90NC5m/dGNkbi5uZXQvanBn/LzAxLzQ3LzIxLzk1/LzM2MF9GXzE0NzIx/OTU5Nl9Zc0RxSnN4/RXlkRFVTTHU0Qm1H/dlpQUEF4ZkpyNTkx/VC5qcGc',1,''),(7,'canjeelo','canjeelo somali',2.00,'',1,''),(8,'Avocado drink ','av',1.00,'https://imgs.search.brave.com/M2KjilyggJ76zFqGIHryy_D9RJKHoCpjFwwOCdBmLJU/rs:fit:500:0:1:0/g:ce/aHR0cHM6Ly93d3cu/c2h1dHRlcnN0b2Nr/LmNvbS9pbWFnZS12/ZWN0b3IvcGVhci1h/dm9jYWRvLXBlYWNo/LWZyZXNoLWZydWl0/cy0yNjBudy0xNDg1/MDk3NjkxLmpwZw',1,'');
/*!40000 ALTER TABLE `menuitem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_address`
--

DROP TABLE IF EXISTS `order_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_address` (
  `address_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `instructions` text,
  PRIMARY KEY (`address_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `order_address_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_address`
--

LOCK TABLES `order_address` WRITE;
/*!40000 ALTER TABLE `order_address` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_address` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orderitem`
--

DROP TABLE IF EXISTS `orderitem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orderitem` (
  `order_item_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `menu_item_id` int NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `menu_item_id` (`menu_item_id`),
  CONSTRAINT `orderitem_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`),
  CONSTRAINT `orderitem_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menuitem` (`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orderitem`
--

LOCK TABLES `orderitem` WRITE;
/*!40000 ALTER TABLE `orderitem` DISABLE KEYS */;
INSERT INTO `orderitem` VALUES (1,1,5,1,2.00),(2,2,5,2,2.00),(3,2,4,1,1.00),(4,3,6,2,4.00),(5,3,5,1,2.00),(6,4,6,1,4.00),(7,4,4,1,1.00),(8,5,6,2,4.00),(9,5,5,2,2.00),(10,6,6,1,4.00),(11,6,5,3,2.00),(12,7,6,2,4.00),(13,7,5,1,2.00),(14,7,4,1,1.00),(15,8,7,2,2.00),(16,9,6,1,4.00),(17,9,5,2,2.00),(18,10,7,4,2.00),(19,10,6,1,4.00);
/*!40000 ALTER TABLE `orderitem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Preparing','On the way','Delivered') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  PRIMARY KEY (`order_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,1,'2025-12-08 11:44:48',5.19,'Delivered','Paid'),(2,1,'2025-12-08 12:54:09',8.49,'Delivered','Paid'),(3,1,'2025-12-08 15:16:41',13.99,'Delivered','Paid'),(4,1,'2025-12-08 15:18:37',8.49,'Delivered','Paid'),(5,1,'2025-12-08 16:58:58',16.19,'Delivered','Paid'),(6,14,'2025-12-13 14:15:14',13.99,'Preparing','Pending'),(7,15,'2025-12-13 14:25:32',15.09,'Delivered','Paid'),(8,16,'2025-12-13 15:02:12',7.39,'Preparing','Pending'),(9,4,'2025-12-13 15:27:34',11.79,'On the way','Pending'),(10,21,'2025-12-20 16:30:24',16.19,'Preparing','Pending');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` enum('Customer','Admin','Delivery') NOT NULL DEFAULT 'Customer',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
INSERT INTO `user` VALUES (1,'masoud abdikarim mohamed  ','0634705869','$2y$10$e0PWj7LhGuU96bNepqowTOxZOq9rZvaJMubsHMXNUiBWJyjhMVbty','Customer',1,'2025-12-04 14:06:50'),(2,'ahmed muse jama','1234705869','$2y$10$6Md5Wg6zAYScs/2U8ckHUeA0EcbfFwr5sqtqOGein7kz3Jr1cxUj.','Customer',1,'2025-12-04 14:06:50'),(3,'sakariye daahir abdi','00000000000000000000','$2y$10$U8/mQTYgHwflPO5oKsXcJOKty/Ocw5EfajBUKNCCU9z59n6XQ.Lfq','Admin',1,'2025-12-04 17:24:16'),(4,'khalid mawlid hirad','1234567890','$2y$10$XXhiZ6RtxQxS.SXI3QgIT.n9etsYu93RRmMjm4lPReGa3JopINjYK','Customer',1,'2025-12-06 13:26:10'),(5,'masoud abdikarim mohamed  ','1234567896543','$2y$10$mh6NY0KZSgh53Jxde3azoOr7Q9ASxuyLG7Q85peojsTTYmJ78.VaO','Delivery',1,'2025-12-06 14:01:51'),(7,'ahmed muse jama','0634705869888','$2y$10$XvlDEc//YPN5yUBj/jHSeOsBrzkpdVAPw.EEAyA.EgZk8oXev0jMq','Delivery',1,'2025-12-06 15:33:36'),(8,'Richard','06347058691111','$2y$10$FdrXnT7x8r.WbLsFvfsmyuFb00.qO2FF3MR6BPrp3QDrfsxm1phae','Delivery',1,'2025-12-06 17:00:35'),(10,'mmmmmmmmm','123456789011','$2y$10$YmlCsQRYam8xNkagTE3PceEiBXDl44gMKi/4TMiuxYAiIiIBNMttO','Admin',1,'2025-12-07 14:14:21'),(11,'ahmed muse jama al','12369876321','$2y$10$LS5amevHIh9OfmMduYpKOO1b14flHk193AWTfJIbZ4jCN0THxCPie','Delivery',1,'2025-12-07 14:17:28'),(12,'masoud abdikarim mohamed  ','06347058690634705869','$2y$10$sKJZgfTrT0eOObXEpdUzY.W/ONzJjKI7QIudnO5bC.k9B4LldmPkq','Admin',1,'2025-12-08 08:20:16'),(13,'muse ateye muse','06347058611','$2y$10$X3QpQ15Ctpb24uCjeDPRzejLAFrYHVBbqo..uC3R0fxh0AHFc775C','Customer',1,'2025-12-08 15:22:41'),(14,'khadar','123456789098','$2y$10$NuvmV57aV1GAVdBNjjk5G.KyTh1n7laLy3MiMoHzte/nhfx31nnnO','Customer',1,'2025-12-13 11:14:49'),(15,'khaalid mawlid hirad','063470586998','$2y$10$GcL6cOORKhgs5kOqZMWD4Ombp2/EMNUJvGFtXEoQ/bZ5JiuMo7Vc2','Customer',1,'2025-12-13 11:24:54'),(16,'maxamed xasan','0633478003','$2y$10$EGoXcUWu1NDy6V8vmGCrbuOSWohozbC4asTtb6e9cLbRIzQYciQ2a','Customer',1,'2025-12-13 11:59:54'),(17,'masoud abdikarim mohamed  ','0657984426','$2y$10$mY85zuDSNBX84LWbYhvIVOuL2RRfFGWXpw3eCd6mJiRPrT2ebkPDe','Customer',1,'2025-12-13 12:09:05'),(18,'subeer saalax','06387654321','$2y$10$m8Bti4hku56.DfXAhlorYut5kefnmmchQov2XxhZ9cypeVnSjSIHK','Customer',1,'2025-12-13 12:15:07'),(19,'muuse','1122334455','$2y$10$zjaQrWIbQQD6PwOMipKoWOXRcaC/hoJbAN/x2PZ73r1z.XBtAWsYu','Customer',1,'2025-12-14 12:32:13'),(20,'masoud abdikarim mohamed  ','063470586911','$2y$10$iPd/4qIWqIACm/82BmOkZOhky.QdEPYOuaGVUpreI2bf4EDbky5YK','Customer',1,'2025-12-20 11:21:07'),(21,'muse ateye muse','123456786543','$2y$10$qIU0a178g3jqR/uD/NV1vujVxKrFSlUQlkvabFApDc/0E9ggpsW16','Customer',1,'2025-12-20 13:29:07');
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-21  7:34:15
