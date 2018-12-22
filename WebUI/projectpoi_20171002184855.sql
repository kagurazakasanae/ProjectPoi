-- MySQL dump 10.13  Distrib 5.7.19, for Linux (x86_64)
--
-- Host: localhost    Database: projectpoi
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
-- Table structure for table `payment_history`
--

DROP TABLE IF EXISTS `payment_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_history` (
  `pid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `hashes` text NOT NULL,
  `xmr` text NOT NULL,
  `tran_id` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_history`
--

LOCK TABLES `payment_history` WRITE;
/*!40000 ALTER TABLE `payment_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `site_users`
--

DROP TABLE IF EXISTS `site_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `site_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `lastsubmit` int(11) NOT NULL,
  `username` text NOT NULL,
  `hashes` text CHARACTER SET gbk NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `site_users`
--

LOCK TABLES `site_users` WRITE;
/*!40000 ALTER TABLE `site_users` DISABLE KEYS */;
INSERT INTO `site_users` VALUES (1,1,1,1506921373,'test','2560');
/*!40000 ALTER TABLE `site_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sites`
--

DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sites` (
  `sid` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `site_name` text NOT NULL,
  `site_key` text NOT NULL,
  `hashes` text NOT NULL,
  `last_hashes` text NOT NULL,
  `speed` int(11) NOT NULL,
  PRIMARY KEY (`sid`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sites`
--

LOCK TABLES `sites` WRITE;
/*!40000 ALTER TABLE `sites` DISABLE KEYS */;
INSERT INTO `sites` VALUES (1,1,'test123','DIW7ccg1Kvpylc0ZhLVfQAGi','100608','100608',0),(2,1,'新加站点','VmMHRuMEpyU9Gev333f3snbp','0','0',0),(3,2,'你的站点','ApzhIhPcvEvV5vouH0yPgw7f','0','0',0),(4,3,'你的站点','7lxpxYLgZuvbo2NEJM4WLTwA','0','0',0),(5,4,'Blog','AyUjSCsudwhhoh06kPC0Ozg6','17152','17152',0),(6,5,'哥特萝莉社','0SKikMu5OhazUJo1Nx7FdS08','9728','9728',0),(7,6,'你的站点','J9bWCQ00drbAapETDVZ3EcL4','0','0',0),(8,4,'Poi2','OFlWGlcQiI8Omx2PDt8UhicZ','0','0',0),(9,7,'你的站点','BLArGxCeSQfJAPeEvPT3joLY','0','0',0),(10,8,'你的站点','3uvcg8pWCXpGoKk4KFmrUWCw','232192','232192',0),(11,9,'喵喵喵~','VNUIQXnPnEjWmjBiriEUPu8f','357376','357376',0),(12,10,'你的站点','iPZuuXfN2reDSGY6GBp2Ho5S','0','0',0);
/*!40000 ALTER TABLE `sites` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system`
--

DROP TABLE IF EXISTS `system`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system` (
  `key` text NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system`
--

LOCK TABLES `system` WRITE;
/*!40000 ALTER TABLE `system` DISABLE KEYS */;
INSERT INTO `system` VALUES ('xmr_pools','[{\"Server\":\"xmr.minercircle.com:3333\",\"Address\":\"47M24aXLuv7aeCiVXAG6RUgi6Ds5Y3WG6cR3dop5Y5J8EtwqA1fBdsYf2yoFiX978Lhrpzz1bi1VMEwF5xsfAmnYDsmxSG9+256\",\"Choose\":true},{\"Server\":\"mine.xmrpool.net:3333\",\"Address\":\"47M24aXLuv7aeCiVXAG6RUgi6Ds5Y3WG6cR3dop5Y5J8EtwqA1fBdsYf2yoFiX978Lhrpzz1bi1VMEwF5xsfAmnYDsmxSG9+256\",\"Choose\":false},{\"Server\":\"pool.xmr.pt:3333\",\"Address\":\"47M24aXLuv7aeCiVXAG6RUgi6Ds5Y3WG6cR3dop5Y5J8EtwqA1fBdsYf2yoFiX978Lhrpzz1bi1VMEwF5xsfAmnYDsmxSG9+256\",\"Choose\":false},{\"Server\":\"monero.lindon-pool.win:3333\",\"Address\":\"47M24aXLuv7aeCiVXAG6RUgi6Ds5Y3WG6cR3dop5Y5J8EtwqA1fBdsYf2yoFiX978Lhrpzz1bi1VMEwF5xsfAmnYDsmxSG9+256\",\"Choose\":false}]'),('xmr_info','{\"diff\":\"32954436977\",\"block_reward\":6.479664963857,\"payout\":0.9,\"last_update\":\"1506994308\"}'),('payment_min','0.3');
/*!40000 ALTER TABLE `system` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(11) NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `api_key` text NOT NULL,
  `is_validated` int(11) NOT NULL,
  `reg_date` int(11) NOT NULL,
  `reg_ip` text NOT NULL,
  `payment_config` text NOT NULL,
  `paid_xmr` text NOT NULL,
  `paid_hashes` text NOT NULL,
  `total_hashes` text NOT NULL,
  `last_total` text NOT NULL,
  `hour_avrg` text NOT NULL,
  PRIMARY KEY (`uid`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@admin.com','99eb555ec0f68a5d7079d9de9c7e0175','H9ZDTr2vHO1VKW4mBIVTWEaF',1,1506764932,'2130706433','{\"address\":\"47M24aXLuv7aeCiVXAG6RUgi6Ds5Y3WG6cR3dop5Y5J8EtwqA1fBdsYf2yoFiX978Lhrpzz1bi1VMEwF5xsfAmnYDsmxSG9 \",\"minpayout\":\"2\"}','0','','100608','100608','[[1506920401,\"4.26667\"],[1506924001,\"4.76444\"],[1506927601,\"0.28444\"],[1506931201,\"3.05778\"],[1506934801,\"5.83111\"],[1506938401,\"0.64000\"],[1506942001,\"0.99556\"],[1506945601,\"2.41778\"],[1506949201,\"2.91556\"],[1506952802,\"0.14222\"],[1506956401,\"0.35556\"],[1506960001,\"0.64000\"],[1506963601,\"0.21333\"],[1506967201,\"0.00000\"],[1506970801,\"0.14222\"],[1506974401,\"0.00000\"],[1506978001,\"0.14222\"],[1506981601,\"0.00000\"],[1506985201,\"0.21333\"],[1506988801,\"0.00000\"],[1506992401,\"0.35556\"]]'),(2,'pashokaku@ccyoursite.pashokaku','81066d75c08e630d7bed7abf3b4d1c2a','oFWjSbdTTWd3FpGx9FXmxjSD',0,1506929999,'1020613421','','0','0','0','0','[[1506931201,\"0.00000\"],[1506934801,\"0.00000\"],[1506938401,\"0.00000\"],[1506942001,\"0.00000\"],[1506945601,\"0.00000\"],[1506949201,\"0.00000\"],[1506952802,\"0.00000\"],[1506956401,\"0.00000\"],[1506960001,\"0.00000\"],[1506963601,\"0.00000\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(3,'i@bobiji.com','0744c55bddcc1ec3e480aa90346f3ba1','B15DFGjMmU5L1BBEzZlUv8xC',1,1506930243,'3389330646','','0','0','0','0','[[1506931201,\"0.00000\"],[1506934801,\"0.00000\"],[1506938401,\"0.00000\"],[1506942001,\"0.00000\"],[1506945601,\"0.00000\"],[1506949201,\"0.00000\"],[1506952802,\"0.00000\"],[1506956401,\"0.00000\"],[1506960001,\"0.00000\"],[1506963601,\"0.00000\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(4,'archebasic@hotmail.com','e0a62e0c12123332a7525b5106ab9749','WMEHwVh07xkPS8hABkdMuDBo',1,1506931353,'244523185','{\"address\":\"43jjxpTEkj6eh9vWVSz8Hrhg7oYe8KNUTU1gKorxbmCuExWLGff5WmVV49rP6R5fjWUoXNPmCP1sUVPw64PesfjjNZFESF5\",\"minpayout\":\"0.3\"}','0','0','17152','17152','[[1506934801,\"0.00000\"],[1506938401,\"0.00000\"],[1506942001,\"0.00000\"],[1506945601,\"0.42667\"],[1506949201,\"4.33778\"],[1506952802,\"0.00000\"],[1506956401,\"0.00000\"],[1506960001,\"0.00000\"],[1506963601,\"0.00000\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(5,'124722330@qq.com','89e2fc2ce4f23403fe96f99527013e0e','GwEKa38LsArrm8pO9EcLpd6Z',1,1506941587,'884682977','{\"address\":\"\",\"minpayout\":\"0.5\"}','0','0','9728','9728','[[1506942001,\"0.00000\"],[1506945601,\"7.11111\"],[1506949201,\"5.12000\"],[1506952802,\"3.69778\"],[1506956401,\"1.77778\"],[1506960001,\"1.56444\"],[1506963601,\"0.07111\"],[1506967201,\"-18.63111\"],[1506970801,\"4.97778\"],[1506974401,\"4.19556\"],[1506978001,\"-9.17333\"],[1506981601,\"1.84889\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.14222\"]]'),(6,'admin@diemoe.net','ae835a8fa36f98c9225bf3848fb7d0f5','WMTGbSvYdB1iJlyd76rub54o',1,1506941736,'1883310916','','0','0','0','0','[[1506942001,\"0.00000\"],[1506945601,\"0.00000\"],[1506949201,\"0.00000\"],[1506952802,\"0.00000\"],[1506956401,\"0.00000\"],[1506960001,\"0.00000\"],[1506963601,\"0.00000\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(7,'foxtxd@qq.com','eb03003f7c0a30c7d9e0123be39f79ca','asTUDWbsJrS9ShOIH0gokp8k',1,1506946470,'826843886','','0','0','0','0','[[1506949201,\"0.00000\"],[1506952802,\"0.00000\"],[1506956401,\"0.00000\"],[1506960001,\"0.00000\"],[1506963601,\"0.00000\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(8,'duzmma@qq.com','fcc98d4e3051b7408efe64c78d0dca6d','88GBAw5W7BMBEGHNwQJX6hHx',1,1506948185,'22135885','','0','0','232192','232192','[[1506949201,\"6.18667\"],[1506952802,\"23.82222\"],[1506956401,\"12.30222\"],[1506960001,\"21.90222\"],[1506963601,\"0.28444\"],[1506967201,\"0.00000\"],[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]'),(9,'49806786@qq.com','302ff8993767775bad3ae16c26ac522c','FJZwSlQ9LQlnx1jTvtSCHdrD',1,1506956289,'884702934','{\"address\":\"4BTumX8TVPqNQdMECyMF8xLhGY9wmTaaZgozK8LHjNAEdUKfa3wryRaMwY3h8V9YntSc9GPHVee6wGEQSuMpJxge8JDq2ej5Mdb4YWXwA3\",\"minpayout\":\"0.3\"}','0','0','357376','295424','[[1506956401,\"0.00000\"],[1506960001,\"0.28444\"],[1506963601,\"-0.07111\"],[1506967201,\"-0.14222\"],[1506970801,\"0.78222\"],[1506974401,\"16.92444\"],[1506978001,\"-17.70667\"],[1506981601,\"29.93778\"],[1506985201,\"0.07111\"],[1506988801,\"22.04444\"],[1506992401,\"29.93778\"]]'),(10,'usrp@protonmail.com','d0d0fdfc6a44b97e9dca811b1b4bb5c3','FeaZdJetaxRme5bbTSfRrQa4',1,1506970442,'2032164861','','0','0','0','0','[[1506970801,\"0.00000\"],[1506974401,\"0.00000\"],[1506978001,\"0.00000\"],[1506981601,\"0.00000\"],[1506985201,\"0.00000\"],[1506988801,\"0.00000\"],[1506992401,\"0.00000\"]]');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2017-10-03  9:49:03
