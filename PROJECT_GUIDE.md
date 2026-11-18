# Laravel Vue Chat - Project Guide

This guide explains how to set up the application, use its features, and follow the main request and real-time message flows.

## 1. What the application does

Laravel Vue Chat is an authenticated, one-to-one messaging application. Users can register or sign in, find other registered users, start or reopen a private conversation, and exchange messages. Messages are stored in the database and delivered to open chat pages in real time through Laravel Reverb, Laravel Echo, and Inertia live props.

## 2. Requirements

- PHP 8.3 or later and Composer
- Node.js and npm
- SQLite for the default local database configuration

## 3. Set up the project

From the project directory:

1. Install PHP dependencies:

   ```bash
   composer install
   ```

2. Create the local environment file if it does not already exist:

   ```powershell
   Copy-Item .env.example .env
   ```

3. Generate the application key:

   ```bash
   php artisan key:generate
   ```

4. The example environment uses SQLite. Create its database file if it is missing, then run migrations:

   ```powershell
   if (-not (Test-Path database\database.sqlite)) { New-Item -ItemType File database\database.sqlite }
   php artisan migrate
   ```

5. Install JavaScript dependencies:

   ```bash
   npm ci
   ```

The `.env.example` file contains local Reverb settings. Keep the Reverb server values (`REVERB_APP_KEY`, `REVERB_APP_SECRET`, and `REVERB_APP_ID`) aligned with the client-facing `VITE_REVERB_*` settings. If you change environment values used by Vite, restart the Vite development server.

## 4. Start the application

Run each process in a separate terminal from the project directory:

```bash
php artisan serve
```

```bash
php artisan reverb:start
```

```bash
npm run dev
```

Then open `http://127.0.0.1:8000`. To build frontend assets for production instead of running Vite in development mode, run:

```bash
npm run build
```

## 5. Use the application

1. Open the app and create an account with a name, email, password, and matching password confirmation. Passwords must be at least eight characters. Registration signs you in and opens the messages page.
2. To test a conversation, register a second account in another browser profile or private window. The people list excludes the currently signed-in account.
3. Select a person from the people list. The app creates a direct conversation or reuses the existing one, then opens it.
4. Write a message and press Enter to send. Use Shift+Enter to add a line break. Messages can contain up to 5,000 characters.
5. Keep the conversation open in both browser sessions to see new messages arrive without refreshing. The chat also shows the Reverb connection state, a temporary typing indicator, and blue double-check marks when the other participant has read your message.
6. Use the sun/moon button to switch between light and dark appearance. Your choice is saved in this browser; before you choose, the app follows your operating-system preference.
7. Use **Sign out** to end the current session.

## 6. How a conversation works

1. An authenticated user selects another user from the messages page.
2. The server sorts the two user IDs and finds or creates a conversation for that pair. The database's unique constraint prevents duplicate direct conversations.
3. Opening the conversation loads its latest 50 messages in chronological order and subscribes the page to that conversation's private Reverb channel.
4. When a participant sends a message, the server validates it, saves it, and updates the conversation timestamp in a database transaction.
5. The server immediately broadcasts a `MessageSent` event on the private conversation channel. A queue worker is not needed for this event.
6. Inertia's live-prop integration updates the page's message list without a full reload. The chat scrolls to the latest message.
7. When the conversation is open in a visible tab, the recipient marks incoming messages as read. The server saves a `read_at` timestamp and broadcasts a `MessagesRead` event so the sender's ticks turn blue in real time.
8. Typing indicators use temporary Echo client whispers. They are not stored as messages in the database.

## 7. Privacy and access control

- Chat pages and message submission require authentication.
- Only the two participants can view a conversation, send messages to it, or subscribe to its private broadcast channel.
- Login requests are rate limited. Registration and login both regenerate the session; logout invalidates it.
- The database stores each message with its conversation and sender.

## 8. Main project areas

- `routes/web.php` - web routes for registration, login, logout, and chat.
- `app/Http/Controllers/AuthController.php` - account registration and session handling.
- `app/Http/Controllers/ChatController.php` - conversation listing, access checks, message history, and message sending.
- `app/Models/Conversation.php`, `app/Models/Message.php`, and `app/Models/User.php` - Eloquent models and relationships.
- `database/migrations/` - user, conversation, and message table definitions.
- `app/Events/MessageSent.php`, `app/Events/MessagesRead.php`, and `routes/channels.php` - real-time message and read-receipt events, plus private channel authorization.
- `resources/js/Pages/Auth/` and `resources/js/Pages/Chat/` - Vue pages for authentication, conversation list, and messaging.
- `resources/js/echo.js` and `resources/js/app.js` - Echo/Reverb and Inertia Vue setup.
- `tests/Feature/ChatTest.php` - feature coverage for account flows, private conversations, message delivery, and access control.

## 9. Run the tests

Run the Laravel test suite with:

```bash
php artisan test
```

The chat feature tests cover registration and login, creating and reusing a conversation, saving and broadcasting messages, read receipts, live message props, and participant-only access.
