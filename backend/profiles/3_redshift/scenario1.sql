CREATE TABLE "demo"."scenario1" (
"id" bigint identity(1,1) NOT NULL,
"name" varchar(32) COLLATE "default" DEFAULT ''::character varying NOT NULL,
"int" int4,
"currency" numeric(18,2),
"float" float4,
"bool" bool,
"date" date,
"datetime" timestamp,
"text" text COLLATE "default",
"email" varchar(100) COLLATE "default",
"varchar_10" varchar(10) COLLATE "default",
"id_detail" int4,
"enum" varchar(255) COLLATE "default",
"default" varchar(255) COLLATE "default" DEFAULT 'Sydney'::character varying NOT NULL,
CONSTRAINT "scenario1_pkey" PRIMARY KEY ("id")
)
