FROM migrate/migrate:v4.13.0

COPY migrations /migrations
COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT ["sh", "/entrypoint.sh"]
