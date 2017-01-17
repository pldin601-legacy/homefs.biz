IMAGE_ID := "peacefulbit/homefs"
CONTAINER_ID := "homefs_service"
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

build:
	docker build -t $(IMAGE_ID) .

dev:
	docker run --rm --env-file $(CURDIR)/.env -v $(CURDIR):/var/www/html/ -p 8080:80 --name $(CONTAINER_ID) $(IMAGE_ID)
