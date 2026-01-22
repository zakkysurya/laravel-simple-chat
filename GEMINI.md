# Simple Chat Application (Laravel + Reverb)

## Project Overview
This is a real-time chat application built with **Laravel 12** and **Laravel Reverb**. It demonstrates the power of first-party WebSocket support in Laravel, featuring both public group chat and private direct messaging, along with presence features like online user lists and typing indicators.

## Technology Stack
- **Backend Framework:** Laravel 12.x
- **Real-time Server:** Laravel Reverb (WebSocket server)
- **Frontend:** Blade Templates + Vanilla JavaScript (ES Modules)
- **Broadcasting Client:** Laravel Echo + Pusher JS
- **Styling:** Bootstrap 5 + TailwindCSS (via Vite)
- **Database:** SQLite (default)

## Architecture & Features

### 1. Real-time Communication
The application uses Laravel Broadcasting with the `reverb` driver.
- **Public Chat:** Uses a Presence Channel named `chat`.
- **Private Chat:** Uses Private Channels named `chat.private.{userId}`.
- **Client-Side:** Managed by `laravel-echo` in `resources/views/chat.blade.php`.

### 2. Key Features
- **Group Chat:** Messages broadcast to all users in the 'chat' channel.
- **Private Messaging:** Users can click on an online user to start a private conversation.
- **Online Users:** Real-time list of online users using Presence Channel `.here()`, `.joining()`, and `.leaving()` methods.
- **Typing Indicators:** Implemented using "Whisper" events (`.whisper('typing', ...)`).
- **History:** Loads chat history from the database when switching between public and private views.

### 3. Database Schema
- **Users:** Standard Laravel authentication.
- **Messages:**
    - `sender_id`: User ID of sender.
    - `receiver_id`: User ID of recipient (nullable). `NULL` indicates a public message.
    - `message`: The text content.

## Setup & Running

### Prerequisites
- PHP 8.2+
- Node.js & NPM
- Composer

### Installation
1. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   touch database/database.sqlite
   php artisan migrate
   ```

3. **Start the Application:**
   This project uses a concurrent script to run all necessary services (Server, Queue, Reverb, Vite) in one command:
   ```bash
   npm run dev
   # OR explicitly:
   # composer run dev
   ```

   *If running manually, you need 3 terminals:*
   1. `php artisan serve` (Web Server)
   2. `php artisan reverb:start` (WebSocket Server)
   3. `npm run dev` (Vite Asset Compilation)

## Key Files

- **Routes:** `routes/web.php` (Contains all logic for chat views, message sending, and history fetching).
- **View:** `resources/views/chat.blade.php` (Single-page chat interface with embedded JS logic).
- **Model:** `app/Models/Message.php` (Eloquent model for chat messages).
- **Events:**
    - `app/Events/MessageSent.php` (Public chat event).
    - `app/Events/PrivateMessageSent.php` (Private chat event).
- **Channels:** `routes/channels.php` (Authorization logic for broadcast channels).

## Development Conventions
- **Routing:** Logic is currently inline in `web.php` for simplicity. For complex features, refactor to Controllers.
- **Frontend:** Uses Vanilla JS modules directly in Blade. No complex build step (Vue/React) required, though Vite is used for asset bundling.
- **Broadcasting:** Always ensure `reverb:start` is running for real-time features to work.
