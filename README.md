# Chatbot Moodle

Welcome to the **Chatbot Moodle** project! This repository contains a chatbot solution designed to integrate with Moodle, the popular open-source learning management system. The project primarily uses PHP, with supporting code in JavaScript and CSS.

## Overview

- **Purpose:**  
  This project aims to enhance Moodle by providing an intelligent chatbot interface. The chatbot can assist users (students, teachers, admins) with common tasks, answer questions, and guide them through Moodle functionalities.

- **Core Technologies:**  
  - **PHP**: Backend logic and Moodle plugin integration (≈ 90% of the codebase)
  - **JavaScript**: Frontend interactivity and communication with the chatbot backend
  - **CSS**: Styling for the chatbot interface

## Features

- Seamless integration as a Moodle plugin or extension
- Interactive chat interface for end-users
- Can provide answers to FAQs, help with course navigation, and basic troubleshooting
- Easily extendable to support new intents and responses

## Getting Started

### Prerequisites

- A working [Moodle](https://moodle.org/) installation (version compatibility may vary)
- PHP 7.4+ recommended
- Web server (e.g., Apache, Nginx)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/DMGS94/chatbot.git
   ```
2. **Copy the plugin to your Moodle installation:**
   - Place the plugin directory (typically named `chatbot` or similar) into `your-moodle-root/local/` or the appropriate plugin folder.
3. **Complete installation via Moodle admin UI:**
   - Log in as admin.
   - Navigate to *Site Administration > Notifications* to trigger plugin installation.
4. **Configure the plugin:**
   - Set up default responses or connect to an external NLP service if required.
   - Adjust permissions and settings via the plugin’s configuration page.

### Usage

- Access the chatbot from the designated area in the Moodle interface (e.g., site footer, sidebar, or a dedicated page).
- Start typing your question or request.
- The chatbot will respond contextually, helping you with navigation, queries, or troubleshooting.

## Customization

- **Adding New Responses:**  
  Modify the PHP logic or configuration files to add new intents and responses.
- **UI Customization:**  
  Edit the JavaScript and CSS files to change the appearance and behavior of the chatbot window.

## Contributing

Contributions are welcome! Please:

- Fork this repository
- Create a new branch (`git checkout -b feature/your-feature`)
- Commit your changes
- Open a pull request with a detailed description

## License

This project is provided under the [MIT License](LICENSE).

## Contact

For issues or suggestions, please open an [issue](https://github.com/DMGS94/chatbot/issues) or contact the maintainer directly.

---

*This project is not officially affiliated with Moodle HQ.*

