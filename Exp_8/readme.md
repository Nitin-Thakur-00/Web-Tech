# Exp_8: Introduction to PHP

Welcome to the Exp_8 folder for the Web Technologies lab. This experiment covers the fundamentals of PHP, including configuration, basic output, data types, and system information rendering.

## 📁 File Structure

```text
Exp_8/
├── Excercise 1/
│   ├── info.php
│   └── Screenshots...
├── Excercise 2/
│   ├── about.php
│   ├── index.php
│   └── Screenshots...
├── Excercise 3/
│   ├── constants.php
│   ├── data_types.php
│   ├── profile.php
│   └── Screenshots...
└── Excercise 4/
    ├── sysinfo.php
    └── Screenshots...
```

## 💻 How to Run

To view these exercises, you can use the built-in PHP development server:

1.  Open your terminal in the `Exp_8/Excercise X` directory.
2.  Run the following command:
    ```bash
    php -S localhost:8000
    ```
3.  Open your browser and navigate to `http://localhost:8000/filename.php`.

Alternatively, you can place this entire `Web-Tech` folder in your local server's root directory (e.g., `htdocs` for XAMPP).

## 🚀 Exercises Overview


## Exercise 1: PHP Info
This exercise demonstrates the use of the `phpinfo()` function to output information about PHP's configuration.

### Files
- [`info.php`](Excercise%201/info.php): Displays PHP installation details and configuration.

### Output
![Exercise 1 Output 1](Excercise%201/Screenshot%202026-04-07%20071321.png)
<br>
![Exercise 1 Output 2](Excercise%201/Screenshot%202026-04-07%20071417.png)
<br>
![Exercise 1 Output 3](Excercise%201/Screenshot%202026-04-07%20072541.png)

---

## Exercise 2: Basic Output
This exercise shows how to use basic `echo` statements to output HTML from PHP scripts.

### Files
- [`about.php`](Excercise%202/about.php): Outputs a basic "About Page" with heading and paragraph tags.
- [`index.php`](Excercise%202/index.php): Outputs a message confirming the server works and prints the current `PHP_VERSION`.

### Output
![Exercise 2 Output 1](Excercise%202/Screenshot%202026-04-07%20072651.png)
<br>
![Exercise 2 Output 2](Excercise%202/Screenshot%202026-04-07%20072919.png)
<br>
![Exercise 2 Output 3](Excercise%202/Screenshot%202026-04-07%20073253.png)

---

## Exercise 3: Constants, Variables, and Data Types
This exercise explores creating constants, working with various primitive data types, and rendering structured content.

### Files
- [`constants.php`](Excercise%203/constants.php): Demonstrates defining and using constants (`define()`), and highlights the difference between single and double-quoted strings.
- [`data_types.php`](Excercise%203/data_types.php): Explores types like String, Integer, Float, Boolean, Null, and Array, along with type checking functions (e.g., `is_string`, `gettype`).
- [`profile.php`](Excercise%203/profile.php): Outputs a formatted user profile using basic variables (`$name`, `$age`), associative arrays, and debugging tools (`var_dump`, `print_r`).

### Output
![Exercise 3 Output 1](Excercise%203/Screenshot%202026-04-07%20074249.png)
<br>
![Exercise 3 Output 2](Excercise%203/Screenshot%202026-04-07%20074351.png)
<br>
![Exercise 3 Output 3](Excercise%203/Screenshot%202026-04-07%20074518.png)

---

## Exercise 4: System Information Page
This exercise combines PHP superglobals, server variables, and date functions to display a beautifully-styled system environment dashboard.

### Files
- [`sysinfo.php`](Excercise%204/sysinfo.php): A single-page dashboard displaying the PHP version, OS, max integer limit, document root, and current server time using CSS for styling. It also demonstrates how to iterate over an array of favorite technologies using a `foreach` loop.

### Output
![Exercise 4 Output 1](Excercise%204/Screenshot%202026-04-07%20075005.png)
