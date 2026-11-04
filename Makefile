# Atajos para el día a día (requiere Docker). En Windows sin make, usa los comandos de cada regla.
.PHONY: up down logs reset test seed shell-db

up:      ## Levanta todo el sistema en http://localhost:8080
	@test -f .env || cp .env.example .env
	docker compose up -d --build
	@echo "Web: http://localhost:8080  ·  Correos de prueba: http://localhost:8025"

down:
	docker compose down

logs:
	docker compose logs -f api worker

reset:   ## Borra la base de datos y vuelve a cargar los datos de demostración
	docker compose down -v
	docker compose up -d --build

test:    ## Pruebas del backend y del frontend
	cd backend && composer check
	cd frontend && npm run lint && npm test

shell-db:
	docker compose exec db mysql -u root -p tutorias
