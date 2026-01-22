# 💬 Laravel Reverb Super Chat

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)
![Reverb](https://img.shields.io/badge/Reverb-WebSocket-blueviolet?style=for-the-badge)
![Status](https://img.shields.io/badge/Status-Active-success?style=for-the-badge)

> **Experience the speed of first-party WebSockets.**  
> A blazing fast, real-time chat application built with **Laravel 12** and **Laravel Reverb**. No third-party services, just pure PHP power.

---

## 🚀 About The Project

Welcome to **Reverb Super Chat**, a demonstration of modern real-time capabilities in the Laravel ecosystem. This project showcases how to build a fully functional chat application with **Public Groups**, **Private Direct Messaging**, and **Live Presence** features—all powered by the new `laravel/reverb` package.

Whether you're looking to learn about WebSockets, looking for a chat starter kit, or just want to see Laravel 12 in action, you're in the right place.

### ✨ Key Features

*   **🌐 Real-Time Public Chat:** Broadcast messages instantly to everyone in the room.
*   **🔒 Private Messaging:** Click any online user to start a secure, 1-on-1 conversation.
*   **🟢 Live Presence:** See who is online and when they join or leave in real-time.
*   **✍️ Typing Indicators:** Know when someone is writing a message to you.
*   **💬 Rich UI:** Beautiful "Speech Bubble" interface with sender/receiver distinction.
*   **📱 Auto-Expanding Input:** A smooth typing experience that grows with your message.
*   **📜 History Management:** Seamlessly loads conversation history when switching chats.

---

## 🛠️ Technology Stack

We use the latest and greatest tools to ensure performance and developer experience.

| Component | Technology | Description |
| :--- | :--- | :--- |
| **Backend** | **Laravel 12** | The robust PHP framework we all love. |
| **WebSockets** | **Laravel Reverb** | First-party, high-performance WebSocket server. |
| **Frontend** | **Blade + Vanilla JS** | Simple yet powerful, no complex SPA builds required. |
| **Client** | **Laravel Echo** | effortless event listening on the client side. |
| **Styling** | **Bootstrap 5** | Clean, responsive, and modern UI components. |
| **Database** | **SQLite / PostgreSQL** | Flexible storage for chat history. |

---

## 🏁 Getting Started

Follow these simple steps to get your chat server running in minutes.

### Prerequisites

*   PHP 8.2 or higher
*   Node.js & NPM
*   Composer

### Installation

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/yourusername/simple-chat.git
    cd simple-chat
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    npm install
    ```

3.  **Configure Environment**
    ```bash
    cp .env.example .env
    php artisan key:generate
    touch database/database.sqlite # If using SQLite
    php artisan migrate
    ```

4.  **Start the Engine! 🏎️**
    We use a single command to launch the Web Server, Queue Worker, Reverb Server, and Vite:
    ```bash
    npm run dev
    ```
    *Alternatively, run `composer run dev`.*

---

## 🎮 How to Use

### 1. Run the Application
Before accessing the chat, ensure all services are running. You can do this with a single command:

```bash
npm run dev
```

*This command uses `concurrently` to start:*
*   `php artisan serve` (Web Server)
*   `php artisan reverb:start` (WebSocket Server)
*   `php artisan queue:listen` (Queue Worker)
*   `npm run dev` (Vite Asset Compilation)

### 2. Access the Chat
1.  **Open Browser 1:** Navigate to `http://localhost:8000/chat/1` (Auto-login as **User 1**).
2.  **Open Browser 2 (Incognito):** Navigate to `http://localhost:8000/chat/2` (Auto-login as **User 2**).

### 3. Chat Away!
*   Type in the main box to chat in the **Global Group**.
*   Click on **User 2** in the "Online Users" list to switch to **Private Mode**.
*   Watch the magic happen as messages fly instantly between windows! ⚡

---

## 📂 Project Structure

A quick look at where the magic lives:

*   `routes/web.php` - **The Brain.** Handles routing, message logic, and history fetching.
*   `resources/views/chat.blade.php` - **The Face.** A single-file view containing the UI and WebSocket logic.
*   `app/Events/` - **The Signals.** `MessageSent` (Public) and `PrivateMessageSent` (Direct) events.
*   `routes/channels.php` - **The Bouncer.** Manages authorization for private and presence channels.

---

## 🤝 Contributing

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

1.  Fork the Project
2.  Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3.  Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4.  Push to the Branch (`git push origin feature/AmazingFeature`)
5.  Open a Pull Request

---

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

<p align="center">
  Built with ❤️ using <strong>Laravel</strong>
</p>