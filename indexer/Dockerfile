FROM perl:5.30

RUN apt-get update && apt-get install -y mediainfo ffmpeg && apt-get clean

RUN cpan install Digest::MD5 \
                 Time::HiRes \
                 DBI \
                 DBD::mysql \
                 Syntax::Keyword::Try

COPY . /code
COPY entrypoint.sh /entrypoint.sh
WORKDIR /code

CMD [ "sh", "/entrypoint.sh" ]
