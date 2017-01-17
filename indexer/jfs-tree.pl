#!/usr/bin/perl

use DBI;
use Digest::MD5 qw(md5_hex);
use Time::HiRes qw(time);
use Cwd;
use Encode;
use FindBin qw($Bin);
use Data::Dumper;

my $db_host = 'localhost';
my $db_base = 'jfs3';
my $db_user = 'root';
my $db_pass = '';


my $dsn = "dbi:mysql:$db_base:$db_host:3306";
my $dbh = DBI->connect($dsn, $db_user, $db_pass);

$dbh->do("SET NAMES 'utf8'");

inception(0);

$dbh->disconnect();

sub inception {
	my $parent = shift;
	my $q = $dbh->prepare("SELECT * FROM `dirs` WHERE `parent_id` = ?");
	$q->execute($parent);
	while(@row = $q->fetchrow_array()) {
		my ($r) = get_parents_lr($parent);
		my $nl = $r;
		my $nr = $r + 1;
		$dbh->do("UPDATE dirs_tree SET lft = lft + 2 WHERE lft >= ?", undef, $r);
		$dbh->do("REPLACE INTO dirs_tree VALUES(?, ?, ?)", undef, $row[0], $nl, $nr);
		inception($row[0]);
	}
}

sub get_parents_lr {
	my $parent = shift;
	if($parent == 0) {
		my $q = $dbh->prepare("SELECT IFNULL(rgt, 0) FROM dirs_tree WHERE id = ?");
	} else {
		my $q = $dbh->prepare("SELECT IFNULL(MAX(rgt, 0)) FROM dirs_tree");
	}
	$q->execute($parent);
	if($q->rows() == 1) {
		return $q->fetchrow_array();
	} else {
		return (0);
	}
}

sub current_dir_id {
	my ($path, $parent_id) = @_;
	my $mtime = (stat($path))[9];
	my $gid = 0;
	$q = $dbh->prepare("SELECT `id`, `modified` FROM `dirs` WHERE `name` = ? AND `parent_id` = ?");
	$q->execute($path, $parent_id);
	if($q->rows()) {
		my ($id, $tm) = $q->fetchrow_array();
		$dbh->do("UPDATE `dirs` SET `modified` = ? WHERE `id` = ?", undef, $mtime, $id) if($tm != $mtime);
		$gid = $id;
	} else {
		my $s_time = time();
		$dbh->do('INSERT INTO `dirs` (`name`, `parent_id`, `modified`) VALUES (?, ?, ?);', undef, $path, $parent_id, $mtime);
		my $updtime = time() - $s_time;
		$gid = $dbh->last_insert_id(undef, undef, 'dirs', 'id');
		print "ID $gid parent ${parent_id} time ${updtime}\n";
		#sleep 1;
	}
	$q->finish();
	return $gid;
}

