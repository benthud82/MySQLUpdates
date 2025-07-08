Table: delivery_dates
Columns:
WHSE int(11) PK 
WCSNUM int(11) PK 
WONUM int(11) PK 
BOXNUM int(11) PK 
SHIPZONE varchar(45) 
SHIPCLASS varchar(45) 
TRACER varchar(45) 
BOXSIZE varchar(45) 
HAZCLASS varchar(45) 
BOXLINES int(11) 
BOXWEIGHT decimal(10,2) 
ZIPCODE int(11) 
BOXVALUE decimal(12,2) 
DELIVERDATE date 
DELIVERTIME time 
LICENSE int(11) 
CARRIER varchar(45) 
SHIPDATE date 
SHIPTIME time 
BILLTO int(11) 
SHIPTO int(11) 
SHOULDDAYS decimal(6,2) 
ACTUALDAYS decimal(6,2) 
LATE int(2)


Table: delivery_dates_merge
Columns:
WHSE int(11) PK 
WCSNUM int(11) PK 
WONUM int(11) PK 
BOXNUM int(11) PK 
SHIPZONE varchar(45) 
SHIPCLASS varchar(45) 
TRACER varchar(45) 
BOXSIZE varchar(45) 
HAZCLASS varchar(45) 
BOXLINES int(11) 
BOXWEIGHT decimal(10,2) 
ZIPCODE int(11) 
BOXVALUE decimal(12,2) 
DELIVERDATE date 
DELIVERTIME time 
LICENSE int(11) 
CARRIER varchar(45) 
SHIPDATE date 
SHIPTIME time 
BILLTO int(11) 
SHIPTO int(11) 
SHOULDDAYS decimal(6,2) 
ACTUALDAYS decimal(6,2) 
LATE binary(1)


Table: delivery_dates_only
Columns:
WHSE int(11) PK 
WCSNUM int(11) PK 
WONUM int(11) PK 
BOXNUM int(11) PK 
SHIPZONE varchar(45) 
SHIPCLASS varchar(45) 
TRACER varchar(45) 
BOXSIZE varchar(45) 
HAZCLASS varchar(45) 
BOXLINES int(11) 
BOXWEIGHT decimal(10,2) 
ZIPCODE int(11) 
BOXVALUE decimal(12,2) 
DELIVERDATE date 
DELIVERTIME time 
LICENSE int(11) 
CARRIER varchar(45) 
SHIPDATE date 
SHIPTIME time 
BILLTO int(11) 
SHIPTO int(11)


Table: tnt_summary
Columns:
tnt_billto int(11) PK 
tnt_shipto int(11) PK 
tnt_boxes_mnt int(11) 
tnt_late_mnt int(11) 
tnt_mnt_ontime decimal(5,4) 
tnt_boxes_qtr int(11) 
tnt_late_qtr int(11) 
tnt_qtr_ontime decimal(5,4) 
tnt_boxes_r12 int(11) 
tnt_late_r12 int(11) 
tnt_r12_ontime decimal(5,4) 
tnt_avg_mnt decimal(6,2) 
tnt_avg_qtr decimal(6,2) 
tnt_avg_r12 decimal(6,2)