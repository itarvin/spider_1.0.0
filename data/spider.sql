create database address;
use address;

drop table if exists addses;
create table addses
(
	id int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
	links varchar(50) NOT NULL COMMENT '链接',
	texts varchar(50) NOT NULL COMMENT '值',
	pid varchar(200) NOT NULL DEFAULT '' COMMENT '上级',
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='地址';
