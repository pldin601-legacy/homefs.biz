#!/usr/bin/perl

use strict;
use warnings;
use Syntax::Keyword::Try;
use DBI;
use Digest::MD5 qw(md5_hex);
use Time::HiRes qw(time);
use Cwd;
use Encode;
use FindBin qw($Bin);
use Data::Dumper;

my $db_host = $ENV{'MYSQL_HOSTNAME'};
my $db_base = $ENV{'MYSQL_DATABASE'};
my $db_user = $ENV{'MYSQL_USER'};
my $db_pass = $ENV{'MYSQL_PASSWORD'};

my $ff_cmd = "ffmpeg";

my @target = ('/content');

my @exclude = ('.snap');
my %tof = ();

my @meta_enabled = split(",", lc("669,BMP,DKR,DMF,FAR,FL,FLC,FLI,GIF,ICO,LBM,MDL,MKV,MOD,MOV,MP2,MP3,MTM,NFO,PCX,PIX,S3M,SBK,STM,TGA,ULT,VOB,WAV,XM,APE,AVI,FLAC,JPEG,JPG,M4A,M4V,MP4,PNG,TIF"));
my @wave_enabled = split(",", lc("MP2,MP3,APE,FLAC,M4A,MP4"));

my $dsn = "dbi:mysql:$db_base:$db_host:3306";
my $dbh = DBI->connect($dsn, $db_user, $db_pass);

$dbh->do("SET NAMES 'utf8'");

my $start = time();
my $prev_path = "";

foreach my $path (@target) {
    scan_here($path, undef);
}

$dbh->disconnect();

print "\nDuration: ", (time() - $start), " seconds.\n";

sub scan_here {
    my ($path, $parent_id) = @_;
    my $this_id = current_dir_id($path, $parent_id);

    my @dirs = (), my @files = ();
    opendir(DIR, $path);
    my @file_sort = readdir(DIR);
    closedir(DIR);
    @file_sort = sort(@file_sort);
    while (my $file = shift(@file_sort)) {
        next if ($file eq "." || $file eq ".."); # skip special files
        next if (substr($file, 0, 1) eq ".");    # skip hidden files
        next if (dir_excluded($file));
        my $tmp = $path . "/" . $file;
        next if (-l $tmp);
        if (-d $tmp && -x $tmp) {
            push(@dirs, $file);
        }
        elsif (-f $tmp && -r $tmp) {
            push(@files, $file);
        }
    }
    chdir($path);
    foreach my $file (@files) {
        scan_file($path, $file, $this_id);
    }
    foreach my $dir (@dirs) {
        scan_here($dir, $this_id);
    }
    chdir("..");
}

sub scan_file {
    my ($directory, $name, $parent_id) = @_;
    my ($mtime, $fsize) = (stat($name))[9, 7];

    my $extension;
    if (index($name, '.') > -1) {
        $extension = lc((split('\.', $name))[-1]);
    }
    else {
        $extension = "";
    }

    my $q = $dbh->prepare("SELECT `id`,`modified`,`md5` FROM `files` WHERE `name` = ? AND `dir_id` = ?");
    $q->execute($name, $parent_id);
    if ($q->rows == 1) {
        my ($id, $tm, $md5) = $q->fetchrow_array();
        if ($tm != $mtime || $md5 eq '') {
            my @meta = ffmpeg_get_info($name) if (is_meta_enabled($extension));
            my $cwd = getcwd;
            my $metaj = join(',', @meta);
            my $md5 = file_md5_hex($name);
            my $tags = lc(get_tags("$cwd,$name,$metaj,$md5"));
            $dbh->do("UPDATE `files` SET `modified` = ?, `size` = ?, `md5` = ?, `tags` = ? WHERE `id` = ?", undef, $mtime, $fsize, $md5, $tags, $id);
            mark_reindexed($parent_id);
            if (has_defined_elements(@meta)) {
                $dbh->do("REPLACE INTO `metadata` (`id`, `artist`, `title`, `duration`, `bitrate`, `width`, `height`, `album`, `genre`, `date`, `album_artist`, `tracknumber`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", undef, $id, @meta);
            }
            if (is_wave_enabled($extension)) {
                my ($wave, $scale) = ffmpeg_get_wave($name);
                print "Scale $scale\n";
                $dbh->do("REPLACE INTO `waveform` (`id`, `data`, `scale`) VALUES (?, ?, ?)", undef, $id, $wave, $scale);
            }
        }
    }
    else {
        my @meta = ffmpeg_get_info($name) if (is_meta_enabled($extension));
        my $cwd = getcwd;
        my $metaj = join(',', @meta);
        my $md5 = file_md5_hex($name);
        my $tags = lc(get_tags("$cwd,$name,$metaj,$md5"));
        $dbh->do("INSERT INTO `files` (`name`, `md5`, `extension`, `dir_id`, `modified`, `size`, `tags`, `rnd`) VALUES (?, ?, ?, ?, ?, ?, ?, RAND() * 100000000)", undef, $name, $md5, $extension, $parent_id, $mtime, $fsize, $tags);
        my $fid = $dbh->last_insert_id(undef, undef, "files", "id");
        mark_reindexed($parent_id);
        if (has_defined_elements(@meta)) {
            $dbh->do("REPLACE INTO `metadata` (`id`, `artist`, `title`, `duration`, `bitrate`, `width`, `height`, `album`, `genre`, `date`, `album_artist`, `tracknumber`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", undef, $fid, @meta);
        }
        if (is_wave_enabled($extension)) {
            my ($wave, $scale) = ffmpeg_get_wave($name);
            $dbh->do("REPLACE INTO `waveform` (`id`, `data`, `scale`) VALUES (?, ?, ?)", undef, $fid, $wave, $scale);
        }

    }
    $q->finish();
}

sub is_meta_enabled {
    my $ft = lc(shift);
    foreach my $mt (@meta_enabled) {
        return 1 if ($mt eq $ft)
    }
    return 0;
}

sub is_wave_enabled {
    my $ft = lc(shift);
    foreach my $mt (@wave_enabled) {
        return 1 if ($mt eq $ft)
    }
    return 0;
}

sub has_defined_elements {
    foreach my $el (@_) {
        return 1 if defined $el;
    }
    return 0;
}

sub dir_excluded {
    my $mydir = getcwd() . '/' . shift;
    foreach my $path (@exclude) {
        my $q = quotemeta($path);
        return 1 if ($mydir =~ /^$q/);
    }
    return 0;
}

sub mark_reindexed {
    my $id = shift;
    my $unix = time;
    my $q = $dbh->prepare("SELECT `lft`, `rgt` FROM `dirs_tree` WHERE `id` = ?");
    $q->execute($id);
    my ($lft, $rgt) = $q->fetchrow_array();
    $dbh->do("UPDATE `dirs_tree` SET `reindexed` = ? WHERE (`lft` <= ? AND `rgt` >= ?) OR (`id` = ?)", undef, $unix, $lft, $rgt, $id);
}

sub current_dir_id {
    my ($path, $parent_id) = @_;
    my $mtime = (stat($path))[9];
    my $gid = 0;
    my $q = $dbh->prepare("SELECT `id`, `modified` FROM `dirs` WHERE `name` = ? AND `parent_id` <=> ?");
    $q->execute($path, $parent_id);
    if ($q->rows()) {
        my ($id, $tm) = $q->fetchrow_array();
        if ($tm != $mtime) {
            print "Modified dir!\n";
            $dbh->do("UPDATE `dirs` SET `modified` = ? WHERE `id` = ?", undef, $mtime, $id);
            mark_reindexed($parent_id);
        }
        $gid = $id;
    }
    else {
        my $s_time = time();
        $dbh->do('INSERT INTO `dirs` (`name`, `parent_id`, `modified`) VALUES (?, ?, ?)', undef, $path, $parent_id, $mtime);
        my $updtime = time() - $s_time;
        $gid = $dbh->last_insert_id(undef, undef, 'dirs', 'id');
        print "ID $gid parent ${parent_id} time ${updtime}\n";
        mark_reindexed($parent_id);
        #sleep 1;
    }
    $q->finish();
    return $gid;
}


sub file_md5_hex($$) {
    my $file = shift;
    my $cwd = getcwd;
    print "Calculating MD5\n";
    print "------------------------------------------------------------\n";
    print "File: $file\n";
    print "Path: $cwd\n";
    print "Size: ", human_size((stat($file))[7]), "\n";
    my $digest;
    if (open(FILE, "<", $file)) {
        binmode(FILE);
        $digest = Digest::MD5->new->addfile(*FILE)->hexdigest;
        close(FILE);
    }
    else {
        $digest = "no access";
    }
    print "MD5 : $digest\n";
    print "------------------------------------------------------------\n\n";
    return $digest;
}

sub get_tags($$) {
    my $input = shift;
    my @wd = uwords($input);
    my %tmp = ();
    @wd = grep {!$tmp{$_}++} @wd;
    my @ok = ();
    for my $i (0 .. $#wd) {
        push(@ok, $wd[$i]) if (length($wd[$i]) > 2);
    }
    return join(',', @ok);
}

sub human_size {
    my $bytes = shift;
    my @el = split(" ", "Bytes KiB MiB GiB TiB");
    my $pw;
    for ($pw = 0; $bytes > 1024; $pw++) {
        $bytes /= 1024;
    }
    return sprintf(($pw > 0 ? "%1.1f %s" : "%d %s"), $bytes, $el[$pw]);
}

sub uwords {
    my $string = shift;
    my $min_wd_len = 2;
    my @words = split(/[\[\]\{\}\.\,\_\s\+\-\<\>\(\)\~\*\"\/\\\\]+/, $string);
    my @uWords = ();
    foreach my $word (@words) {
        next if (length($word) < $min_wd_len);
        my $word_tr = $word;
        push(@uWords, $word_tr) if (!testVal(@uWords, $word_tr));
    }
    return @uWords;
}

sub testVal {
    my @arr = shift;
    my $val = shift;
    foreach my $itm (@arr) {
        return 1 if ($val && $val eq $itm);
    }
    return 0;
}

sub esc_chars {
    my $arg = shift;
    $arg =~ s/([\s;<>\*\|`&\$!#\(\)\[\]\{\}:'"])/\\$1/g;
    return "$arg";
}

sub my_exec {
    my $cmd = shift;
    my $buff = "";
    open SHELL, "${cmd}|";
    while (my $line = <SHELL>) {
        $buff .= $line;
    }
    close SHELL;
    return $buff;
}

sub sub_time2sec {
    my $gettime = shift;
    my @digs = split(':', $gettime);
    return undef unless defined $gettime;

    if ($#digs == 2) {
        return abs($digs[0]) * 3600 + abs($digs[1]) * 60 + abs($digs[2]);
    }
    else {
        return 0;
    }
}


# Media Information Section 
sub ffmpeg_get_info {
    my $in_file = shift;
    my $in_file_arg = esc_chars($in_file);
    my $results = my_exec("$ff_cmd -i $in_file_arg 2>&1");

    my ($artist) = $results =~ m/ARTIST\s*\:\s(.*)/ig;
    my ($tn, $title) = $results =~ m/(TITLE|NAME)\s*\:\s(.*)/ig;
    my ($genre) = $results =~ m/GENRE\s*\:\s(.*)/ig;
    my ($album) = $results =~ m/ALBUM\s*\:\s(.*)/ig;
    my ($albumartist) = $results =~ m/ALBUM\_ARTIST\s*\:\s(.*)/ig;
    my ($date) = $results =~ m/DATE\s*\:\s(.*)/ig;
    my ($tracknumber) = $results =~ m/TRACK\s*\:\s(.*)/ig;

    my ($duration, $bitrate) = $results =~ m/Duration: (.*?),.*?bitrate: (.*?) kb\/s/;
    my ($width, $height) = $results =~ m/Stream\s.+Video: .*?, .*?, (\d+)x(\d+)/;

    print "- File Metadata --------------------------------------------\n";
    show_meta_param("Artist", $artist);
    show_meta_param("Title", $title);
    show_meta_param("Album", $album);
    show_meta_param("Genre", $genre);
    show_meta_param("Album Artist", $albumartist);
    show_meta_param("Date", $date);
    show_meta_param("Track Number", $tracknumber);
    show_meta_param("Duration", $duration);
    show_meta_param("Bitrate (kbps)", $bitrate);
    show_meta_param("Width", $width);
    show_meta_param("Height", $height);
    print "------------------------------------------------------------\n";
    return (
        $artist,
        $title,
        sub_time2sec($duration),
        $bitrate,
        $width,
        $height,
        $album,
        $genre,
        $albumartist,
        $date,
        $tracknumber
    );
}

sub show_meta_param {
    my $title = shift;
    my $data = shift;
    printf("%-15s : %s\n", $title, $data ? $data : 'NULL');
    return 1;
}

sub ffmpeg_get_wave {

    my $in_file = shift;
    my $in_file_arg = esc_chars($in_file);
    print "WAVE: Scanning file...\n";
    my $results = my_exec("$ff_cmd -i $in_file_arg -ac 1 -ar 1024 -f u8 -acodec pcm_u8 - 2>/tmp/null");
    print "WAVE: Scanned " . length($results) . " bytes\n";
    my @waveoctets = ();

    my $rate = int(length($results) / 4096);

    if ($rate >= 1) {
        print "WAVE: Slicing...\n";
        my @slices = $results =~ /(.{$rate})/g;
        for my $data (@slices) {
            push(@waveoctets, get_maximum($data));
        }
    }
    else {
        my @slices = $results =~ /(.{1})/g;
        for my $data (@slices) {
            my $i = abs(ord($data) - 127);
            push(@waveoctets, chr($i));
        }
    }

    printf "WAVE: Generated %s bytes\n", ($#waveoctets + 1);
    my $wave = join('', @waveoctets);
    return ($wave, scale_rate($wave));

}

sub get_maximum {
    my $string = shift;
    my $max = 0;
    my $length = length($string);
    for (my $n = 1; $n < $length; $n += 2) {
        my $el = ord(substr($string, $n, 1)) - 127;
        if ($el > $max) {
            $max = $el;
        }
    }
    return chr($max);
}

sub scale_rate {
    my $string = shift;
    my $max = 0;
    my $length = length($string);
    for (my $n = 1; $n < $length; $n++) {
        my $el = ord(substr($string, $n, 1));
        if ($el > $max) {
            $max = $el;
        }
    }
    return 1 / 127 * $max;
}
