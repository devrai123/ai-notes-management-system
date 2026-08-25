# AI Notes Management System

AI-powered Notes Management System built using Laravel, MySQL and OpenAI APIs.

## Features

- Notes CRUD APIs
- Request validation
- Pagination
- JSON API responses
- AI-generated note summaries
- AI semantic search using embeddings
- Cosine similarity search
- Simple AI-powered frontend
- API rate limiting
- Feature tests
- Docker support

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+
- Docker
- OpenAI API key

## Installation

```bash
git clone YOUR_REPOSITORY_URL
cd ai-notes

composer install

cp .env.example .env

php artisan key:generate