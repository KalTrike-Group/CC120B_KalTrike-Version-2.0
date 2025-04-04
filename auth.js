// DOM Elements
const authForms = document.getElementById('auth-forms');
const authError = document.getElementById('auth-error');

// User data storage (simulated database)
let users = JSON.parse(localStorage.getItem('users')) || [];

// Current user session
let currentUser = JSON.parse(localStorage.getItem('currentUser')) || null;

// Initialize the authentication system
function initAuth() {
    // Auto-focus first input field
    const firstInput = authForms.querySelector('input');
    if (firstInput) firstInput.focus();
    
    // Check for redirect from protected route
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('redirect')) {
        showError('Please login to access that page');
    }
}

function showError(message) {
    authError.textContent = message;
    authError.classList.remove('hidden');
    setTimeout(() => authError.classList.add('hidden'), 5000);
}

// Render login form
function renderLoginForm() {
    authForms.innerHTML = `
        <div id="auth-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
        <form id="loginForm" class="space-y-4" autocomplete="on">
            <div>
                <label for="loginEmail" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="loginEmail" required autocomplete="email"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                    value="${localStorage.getItem('lastEmail') || ''}">
            </div>
            <div>
                <label for="loginPassword" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="loginPassword" required autocomplete="current-password"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="flex items-center">
                <input id="rememberMe" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                <label for="rememberMe" class="ml-2 block text-sm text-gray-700">Remember me</label>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Sign In
                </button>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Don't have an account? 
                    <button type="button" onclick="renderRegisterForm()" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign up
                    </button>
                </p>
            </div>
        </form>
    `;

    document.getElementById('loginForm').addEventListener('submit', handleLogin);
}

// Render registration form
function renderRegisterForm() {
    authForms.innerHTML = `
        <div id="auth-error" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"></div>
        <form id="registerForm" class="space-y-4" autocomplete="on">
            <div>
                <label for="registerName" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" id="registerName" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="registerEmail" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="registerEmail" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="registerPassword" class="block text-sm font-medium text-gray-700">Password (min 8 characters)</label>
                <input type="password" id="registerPassword" minlength="8" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label for="userType" class="block text-sm font-medium text-gray-700">I am a:</label>
                <select id="userType" required 
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="passenger">Passenger</option>
                    <option value="driver">Tricycle Driver</option>
                </select>
            </div>
            <div class="flex items-center justify-between">
                <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Account
                </button>
            </div>
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <button type="button" onclick="renderLoginForm()" class="font-medium text-indigo-600 hover:text-indigo-500">
                        Sign in
                    </button>
                </p>
            </div>
        </form>
    `;

    document.getElementById('registerForm').addEventListener('submit', handleRegister);
}

// Handle login form submission
function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    // Simple validation
    if (!email || !password) {
        showError('Please fill in all fields');
        return;
    }

    // Find user in database
    const user = users.find(u => u.email === email && u.password === password);
    
    if (user) {
        // Set current user session
        currentUser = user;
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        
        // Remember email if checked
        if (rememberMe) {
            localStorage.setItem('lastEmail', email);
        } else {
            localStorage.removeItem('lastEmail');
        }
        
        // Redirect based on user type
        const redirectUrl = user.userType === 'driver' 
            ? 'driver-dashboard.html' 
            : 'user-dashboard.html';
            
        window.location.href = redirectUrl;
    } else {
        showError('Invalid email or password');
    }
}

// Handle registration form submission
function handleRegister(e) {
    e.preventDefault();
    const name = document.getElementById('registerName').value;
    const email = document.getElementById('registerEmail').value;
    const password = document.getElementById('registerPassword').value;
    const userType = document.getElementById('userType').value;

    // Validation
    if (!name || !email || !password) {
        showError('Please fill in all fields');
        return;
    }

    if (password.length < 8) {
        showError('Password must be at least 8 characters');
        return;
    }

    // Check if user already exists
    if (users.some(u => u.email === email)) {
        showError('Email already registered');
        return;
    }

    // Create new user
    const newUser = {
        id: Date.now().toString(),
        name,
        email,
        password,
        userType,
        createdAt: new Date().toISOString(),
        lastLogin: new Date().toISOString()
    };

    try {
        // Add to database
        users.push(newUser);
        localStorage.setItem('users', JSON.stringify(users));

        // Set as current user and redirect
        currentUser = newUser;
        localStorage.setItem('currentUser', JSON.stringify(currentUser));
        localStorage.setItem('lastEmail', email);
        
        const redirectUrl = userType === 'driver' 
            ? 'driver-dashboard.html' 
            : 'user-dashboard.html';
            
        window.location.href = redirectUrl;
    } catch (error) {
        showError('Error saving user data. Please try again.');
        console.error('Registration error:', error);
    }
}

// Check if user is already logged in
function checkAuth() {
    if (currentUser) {
        if (currentUser.userType === 'driver') {
            window.location.href = 'driver-dashboard.html';
        } else {
            window.location.href = 'user-dashboard.html';
        }
    }
}

// Initialize authentication system when page loads
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    initAuth();
});