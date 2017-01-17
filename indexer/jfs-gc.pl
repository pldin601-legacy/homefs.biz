#!/usr/bin/perl

use DBI;
use Digest::MD5 qw(md5_hex);
use Time::HiRes qw(time);
use Cwd;
use FindBin qw($Bin);
use Encode qw(encode decode);

#require $Bin . '/core.pl';

my $db_host = 'localhost';
my $db_base = 'jfs3';
my $db_user = 'root';
my $db_pass = '';

my $dsn = "dbi:mysql:$db_base:$db_host:3306";
my $dbh = DBI->connect($dsn,$db_user,$db_pass);

$dbh->do("SET NAMES 'utf8'");

my $start = time();

print "Loading dirs...\n";
my %dirs_array = ();
$dirs = $dbh->prepare("SELECT * FROM `dirs` ORDER BY `parent_id`");
$dirs->execute();
while($dirrow = $dirs->fetchrow_hashref()) {
	@{$dirs_array{$dirrow->{id}}} = ($dirrow->{name}, $dirrow->{parent_id});
}

print "Checking dirs...\n";
# check all root nodes
foreach $key (keys %dirs_array) {
	@nodes = get_ids_by_dir_id($key);
	if($nodes[-1] != 0) {
		print "Broken branch: ", @{$dirs_array{$key}}[0], " -> ", join(",", @nodes), "\n";
		truncate_ids(@nodes);
	} else {
		$fp = get_full_path_by_dir_id($key);
		if(! -d $fp) {
			print "Directory \"$fp\" not found\n";
			truncate_ids($key);
		}
	}
}

print "Checking files...\n";
$files = $dbh->prepare("SELECT * FROM `files` ORDER BY `dir_id`");
$files->execute();

while($filerow = $files->fetchrow_hashref()) {
	$fn = $filerow->{name};
	$fp = get_full_path_by_dir_id($filerow->{dir_id});
	unless(-e $fp.$fn && $fp ne 'broken path') {
		print "File \"$fn\" not found\n";
		remove_file_from_db($filerow->{id});
	}
}

$dbh->do("OPTIMIZE TABLE `dirs`");
$dbh->do("OPTIMIZE TABLE `dirs_holes`");
$dbh->do("OPTIMIZE TABLE `files`");

$dbh->disconnect();

print "\nTotal time: ", (time()-$start), " seconds.\n";

sub get_full_path_by_dir_id {

	my $d_id = shift;
	my $dir_name = "";
	my $path_hash = "";
	
	while(1) {
		if($d_id == 0) { 						# directory good
			return $path_hash;
		} elsif(exists($dirs_array{$d_id})) { 	# concatenuation
			$path_hash = @{$dirs_array{$d_id}}[0] . '/' . $path_hash;
			$d_id = @{$dirs_array{$d_id}}[1];
		} else { 								# directory/file not found
			return 'broken path';
		}
	}
}

sub truncate_ids {
	my @ids = @_;
	foreach my $id(@ids) {
		$dbh->do("CALL delete_directory(?)", undef, $id);
		delete($dirs_array{$id});
	}
}

sub get_lr_index {
	my $id = shift;
	$q = $dbh->prepare("SELECT lft,rgt FROM dirs_tree WHERE id = ?");
	$q->execute($id);
	return $q->fetchrow_array() if $q->rows();
	return undef;
}

sub get_parent {
	my $id = shift;
	return undef if $id == 0;
	my $q = $dbh->prepare("SELECT `parent_id` FROM `dirs` WHERE `id` = ?");
	$q->execute($id);
	if($q->rows) {
		@row = $q->fetchrow_array();
		return $row[0];
	}
	return undef;
}

sub remove_file_from_db {
	my $f_id = shift;
	$dbh->do("DELETE FROM `files` WHERE `id` = '$f_id'");
}

sub get_ids_by_dir_id {

	my $d_id = shift;
	my @ids = ();
	
	push @ids, $d_id;
	
	while(1) {
		if(exists($dirs_array{$d_id}) && ($d_id != 0)) {
			$d_id = @{$dirs_array{$d_id}}[1];
			push @ids, $d_id;
		} else {
			return @ids;
		}
	}

}

sub mark_reindexed {
	my $id = shift;
	my $unix = time;
	$q = $dbh->prepare("SELECT `lft`, `rgt` FROM `dirs_tree` WHERE `id` = ?");
	$q->execute($id);
	my ($lft, $rgt) = $q->fetchrow_array();
	$dbh->do("UPDATE `dirs_tree` SET `reindexed` = ? WHERE (`lft` <= ? AND `rgt` >= ?)", undef, $unix, $lft, $rgt);
}
