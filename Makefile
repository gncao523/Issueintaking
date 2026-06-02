.PHONY: install setup migrate seed serve worker test npm-install dev build

install:
	composer install

npm-install:
	npm install

setup: install npm-install
	cp -n .env.example .env || true
	php artisan key:generate
	touch database/database.sqlite
	php artisan migrate --force
	php artisan db:seed --force
	npm run build

migrate:
	php artisan migrate

seed:
	php artisan db:seed

serve:
	php artisan serve

worker:
	php artisan queue:work --tries=3

dev:
	@echo "Run in two terminals: make serve && make vite"
	@echo "Optional third: make worker"

vite:
	npm run dev

build:
	npm run build

test:
	php artisan test
