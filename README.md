# Чат с ботом

Учебная практика ИТИС: общий чат с реакциями, командами для LLM и админкой.

## Возможности

- Вход по нику. Занятый ник отклоняется без учёта регистра.
- Общие сообщения и реакции из меню эмодзи, видимые всем участникам.
- Служебные сообщения о входе и выходе, список пользователей онлайн.
- Обмен сообщениями через WebSocket с восстановлением соединения.
- Команды вида `!перевод may the force be with you`: текст подставляется в промпт, ответ модели появляется в чате.
- Админка для создания, изменения, отключения и удаления команд, редактирования системного промпта и проверки запроса к модели.

## Стек

PHP 8.4, Symfony 6.4, Doctrine, PostgreSQL 17, Ratchet, Vue 3, Pinia, Bootstrap 5.
По умолчанию используется локальная `gemma3:4b` через Ollama: она работает без API-ключа и отвечает по-русски. Для другого OpenAI-совместимого API можно задать `LLM_BASE_URL`, `LLM_API_KEY` и `LLM_MODEL`.

## Запуск

Нужны Docker и Docker Compose:

```bash
docker compose up --build -d
```

Чат: http://localhost:8090. Админка: http://localhost:8090/admin.
При первом запуске создаётся администратор `admin` с паролем `admin`. Данные можно задать в корневом `.env` до первого запуска:

```dotenv
ADMIN_LOGIN=admin
ADMIN_PASSWORD=ваш-пароль
APP_PORT=8090
```

Модель загружает контейнер `ollama-pull`; загрузка может занять время. Скачанная модель сохраняется в Docker volume.
Остановить приложение: `docker compose down`. База и модель при этом сохраняются.

## Где что находится

- `frontend/src/views/ChatView.vue` — экран чата; `frontend/src/components` — вход, сообщения и реакции.
- `frontend/src/views/admin` — страницы админки; `frontend/src/stores` — состояние чата и авторизация админа.
- `backend/src/Chat/ChatServer.php` — WebSocket, вход, сообщения, реакции и команды.
- `backend/src/Bot` — разбор команды и подстановка текста в промпт.
- `backend/src/Service` — сообщения, настройки и обращения к LLM.
- `backend/src/Controller/Admin` — HTTP API админки.
- `backend/src/Entity`, `Repository`, `migrations` — хранение данных.

HTTP API работает в контейнере `api`, WebSocket — в `ws`; `web` раздаёт Vue и проксирует запросы. Оба серверных контейнера используют одну базу.

## Простые unit-тесты

```bash
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm --no-deps --entrypoint composer api install
docker compose -f docker-compose.yml -f docker-compose.dev.yml run --rm --no-deps --entrypoint php api bin/phpunit
```
