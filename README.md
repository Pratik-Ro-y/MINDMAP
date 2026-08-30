# MindMap Generator

A web-based mind mapping platform built as a Sem-5 project — create, organize, and visualize ideas with an interactive mindmap editor, ready-made templates, and AI-assisted mindmap generation from uploaded documents.

**Live demo:** [mindmap.xo.je](http://mindmap.xo.je)

## Features

- 🔐 **User accounts** — signup, login, and profile management with hashed passwords
- 🧠 **Interactive mindmap editor** — pan/zoom canvas for building and editing node-based mindmaps
- 📋 **Templates** — start from pre-built structures like SWOT Analysis and Project Plan
- 🤖 **AI-powered generation** — upload a document (PDF/TXT) and automatically generate a mindmap from its content
- 📊 **Dashboard** — view, search, and manage all your saved mindmaps in one place

## Tech Stack

- **Backend:** PHP (PDO for MySQL)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, vanilla JavaScript
- **PDF Parsing:** [smalot/pdfparser](https://github.com/smalot/pdfparser) via Composer
- **AI Model:** Local Phi model served through [Ollama](https://ollama.ai/)

## Getting Started (Local Setup)

### Prerequisites
- PHP 7.4+ with PDO MySQL extension
- MySQL / MariaDB
- [Composer](https://getcomposer.org/)
- [XAMPP](https://www.apachefriends.org/) (or similar local server stack)

### Installation

1. Clone the repository into your local server's document root:
   ```bash
   git clone https://github.com/Pratik-Ro-y/MINDMAP_new.git
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Create the database and import the schema:
   ```sql
   CREATE DATABASE mindmap_generator;
   ```
   Then import `dataabase_setup.sql` into it via phpMyAdmin or the MySQL CLI.

4. Configure your database connection in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'mindmap_generator');
   define('BASE_URL', 'http://localhost/MINDMAP_new/');
   ```

5. Visit the project in your browser and sign up for an account to get started.

## Deployment Notes

This project is deployed on free shared hosting (InfinityFree). A few things to keep in mind if deploying elsewhere:

- Update `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, and `BASE_URL` in `config.php` to match your hosting provider's credentials.
- The `vendor/` folder must be uploaded in full (zip and extract server-side if your host's file manager struggles with many small files).
- The AI-generated-from-image feature relies on Tesseract OCR, which requires a system binary most shared hosts don't provide — this currently works locally but not on free hosting. Text-based file uploads (`.txt`, unsecured `.pdf`) work in both environments.
- Password-protected/secured PDFs are not supported by the parsing library and will return a clear error message.

## Project Structure

```
├── api/
│   ├── ai_generator.php     # Handles file upload + AI mindmap generation
│   ├── delete_mindmap.php
│   └── save_mindmap.php
├── config.php                # DB connection + helper functions
├── index.php                 # Landing page
├── login.php / signup.php    # Authentication
├── dashboard.php              # User's mindmap list
├── editor.php                 # Mindmap canvas editor
├── templates.php              # Browse pre-built templates
├── profile.php                 # Account settings
└── dataabase_setup.sql        # Database schema + seed data
```

## Author

Pratik Roy — B.Sc. Computer Science, Modern College of Arts, Science and Commerce, Pune

## License

This project is for academic purposes as part of a Sem-5 coursework submission.
