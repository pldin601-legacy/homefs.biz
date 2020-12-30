FROM perl:5.30

RUN apt-get update && apt-get install -y mediainfo ffmpeg && apt-get clean

RUN cpan install Digest::MD5 Time::HiRes DBI DBD::mysql

COPY . /code
WORKDIR /code

CMD [ "perl", "./jfs-index.pl" ]
