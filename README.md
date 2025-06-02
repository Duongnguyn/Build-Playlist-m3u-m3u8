# Create-Lists-m3u-m3u8 | Dynamic Playlist Manager | Web App
#### Author: Bocaletto Luca

🚀 **Dynamic Playlist Manager Web App** is a PHP-based application that allows you to manage your media playlist easily. With this tool, you can:

- **Add new items** (each with a title and URL) to the playlist.
- **View the playlist** in a table format and have the option to edit or delete every item (using modal dialogs for editing).
- **Import a playlist** from a JSON file.
- **Export your playlist** in various formats: JSON, .m3u, .m3u8, CSV, and SQL.

All data is persistently stored in a JSON file (`playlist_data.json`), which is automatically created if it does not exist.

---

## Features

- **Add Items:** Easily add new playlist items by entering a title and URL.
- **Edit & Delete:** Modify or remove individual items from your playlist using modal dialogs.
- **Import Playlist:** Paste JSON-formatted data to import an entire playlist.
- **Export Options:** Export your playlist in several popular formats:
  - **JSON:** Standard JSON export.
  - **.m3u / .m3u8:** Playlist formats for media players.
  - **CSV:** Comma-separated values export.
  - **SQL:** SQL commands to recreate the playlist in a database.
- **Data Persistence:** All information is saved in a JSON file, ensuring your data is preserved between sessions.

---

## How It Works

1. **Add an Item:**  
   Use the form at the top of the page to enter a title and a URL. When you submit the form, the item is added to your playlist with a unique ID (using the current timestamp).

2. **Edit or Delete an Item:**  
   The playlist is displayed in a table. For each item, you can:
   - Click **"Edit"** to open a modal where you can update the title and URL.
   - Click **"Delete"** to remove the item (with a confirmation prompt).

3. **Importing Playlists:**  
   Paste valid JSON data into the import section and submit the form to load a new playlist.

4. **Exporting Playlists:**  
   Use the export links provided to download your playlist in the desired format (JSON, .m3u, .m3u8, CSV, or SQL).

---

## Technologies Used

- **PHP:** Backend processing, data storage, and export functionality.
- **JSON:** For data persistence in the file `playlist_data.json`.
- **Bootstrap 5:** Modern, responsive UI components.
- **HTML & JavaScript:** For rendering the interface and handling user interactions.

---

## Setup & Usage

1. **Requirements:**  
   Ensure you have a working PHP environment (e.g., XAMPP, WAMP, or a live server).

2. **Installation:**  
   - Clone or download this repository.
   - Place the project files in your PHP server's document root.

3. **Running the App:**  
   Open your browser and navigate to `http://localhost/your-project-folder/index.php` to start managing your playlist.

---

## License

This project is licensed under the **GPL License**.  
Feel free to use, modify, and distribute it freely!

---

## Author

**Bocaletto Luca**
