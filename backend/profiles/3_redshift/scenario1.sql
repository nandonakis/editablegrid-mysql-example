/*
Navicat PGSQL Data Transfer

Source Server         : reports
Source Server Version : 90218
Source Host           : 172.17.1.170:5432
Source Database       : demo
Source Schema         : demo

Target Server Type    : PGSQL
Target Server Version : 90218
File Encoding         : 65001

Date: 2017-06-06 14:15:42
*/


-- ----------------------------
-- Table structure for scenario1
-- ----------------------------
DROP TABLE IF EXISTS "demo"."scenario1";
CREATE TABLE "demo"."scenario1" (
"id" int4 DEFAULT nextval('"demo".scenario1_id_seq'::regclass) NOT NULL,
"name" varchar(32) COLLATE "default" DEFAULT ''::character varying NOT NULL,
"int" int4,
"currency" numeric(18,2),
"float" float4,
"bool" bool,
"date" date,
"datetime" timestamp(6),
"text" text COLLATE "default",
"email" varchar(100) COLLATE "default",
"varchar_10" varchar(10) COLLATE "default",
"id_detail" int4,
"enum" varchar(255) COLLATE "default",
"default" varchar(255) COLLATE "default" DEFAULT 'Sydney'::character varying NOT NULL
)
WITH (OIDS=FALSE)

;

-- ----------------------------
-- Records of scenario1
-- ----------------------------
INSERT INTO "demo"."scenario1" VALUES ('1', 'Andon', '88', '1.32', '1', 't', '2017-05-05', '2017-05-04 00:00:00', 'asdfasdfasdfadfl;djasfa', 'Nam@quisdiamluctus.org', '1234567890', '1', 'ok', 'FOOBAR');
INSERT INTO "demo"."scenario1" VALUES ('2', 'Atkins', '44', '5.55', '1.5', 't', '2012-03-16', '2017-05-05 12:30:00', null, 'arcu.et.pede@musProin.cam', 'Ishmael', '2', 'not_ok', 'foobar');
INSERT INTO "demo"."scenario1" VALUES ('3', 'Hamilton', '10', '10.50', '1', 't', '2012-03-16', null, null, 'dui@duiCras.edu', 'Mohammad', '3', 'ok', 'FOOBAR');
INSERT INTO "demo"."scenario1" VALUES ('4', 'Nicholas', '18', '1.92', '2', 'f', '2012-03-16', null, null, 'mollis@eutellus.co.uk', 'Troy', '4', null, 'FOOBAR');
INSERT INTO "demo"."scenario1" VALUES ('5', 'Schwartz', '79', '1.50', '1', 'f', '2012-03-16', null, null, 'ante@adipiscing.org', 'Carla', '1', null, 'there');

-- ----------------------------
-- Alter Sequences Owned By 
-- ----------------------------

-- ----------------------------
-- Primary Key structure for table scenario1
-- ----------------------------
ALTER TABLE "demo"."scenario1" ADD PRIMARY KEY ("id");
