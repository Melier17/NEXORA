# NEXORA — Frontend prototype

This folder contains the responsive, static dashboard prototype for NEXORA.

## Preview locally
Open `index.html` in a browser. No build step or package installation is required.

## Publish with GitHub Pages
1. Open the repository settings on GitHub.
2. Go to **Pages**.
3. Under **Build and deployment**, choose **Deploy from a branch** only if publishing from a supported root or `/docs` folder. This project keeps the frontend in `/frontend`, so use a deployment workflow or move/copy the static files to a supported folder.
4. The prototype can also be deployed to any static hosting provider by publishing the contents of this folder.

## Important
This is a visual prototype with sample data. Login, workspace management, projects and tasks are not connected to the PHP/MySQL API yet. Actions that add tasks only change the current browser view and do not save to a database. Never enter real passwords or sensitive information here.

The PHP API remains under `backend/` and requires a PHP/MySQL-capable host. GitHub Pages can serve only the static frontend; it cannot execute the PHP backend.
