#
# Table structure for table 'tx_icsutopia_site'
#
CREATE TABLE tx_icsutopia_site (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	fe_cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	url tinytext,
	be_group int(11) DEFAULT '0' NOT NULL,
	base_model varchar(100) DEFAULT '' NOT NULL,
	title varchar(100) DEFAULT '' NOT NULL,
	main_storage text,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);