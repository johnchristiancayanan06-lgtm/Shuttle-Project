// ═══════════════════════════════════════════════════════════════
//  EASTWEST SHUTTLE SYSTEM - AUTHENTICATION & ACCOUNT MANAGEMENT
// ═══════════════════════════════════════════════════════════════

// ───────────────────────────────────────────────────────────────
//  BASIC HELPERS
// ───────────────────────────────────────────────────────────────

function generateId(prefix = 'user') {
    return `${prefix}_${Date.now()}_${Math.random().toString(36).slice(2)}`;
}

function simpleHash(password) {
    let hash = 0;
    for (let i = 0; i < password.length; i++) {
        hash = ((hash << 5) - hash) + password.charCodeAt(i);
        hash |= 0;
    }
    return hash.toString(36);
}

function saveData(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
}

function loadData(key, defaultValue = null) {
    const data = localStorage.getItem(key);
    return data ? JSON.parse(data) : defaultValue;
}

// ───────────────────────────────────────────────────────────────
//  ROLE PERMISSIONS (MATCHES Accounts + Records)
// ───────────────────────────────────────────────────────────────

const ROLE_PERMISSIONS = {
    "Dispatcher": {
        canViewAccounts: false,
        canCreateAccount: false,
        canUpdateAccount: false,
        canDeleteAccount: false,
        canChangeRole: false,
        canViewRecords: true,
        canExportRecords: true
    },
    "Admin": {
        canViewAccounts: true,
        canCreateAccount: true,
        canUpdateAccount: true,
        canDeleteAccount: false,
        canChangeRole: false,
        canViewRecords: true,
        canExportRecords: true
    },
    "Super Admin": {
        canViewAccounts: true,
        canCreateAccount: true,
        canUpdateAccount: true,
        canDeleteAccount: true,
        canChangeRole: true,
        canViewRecords: true,
        canExportRecords: true
    }
};

function hasPermission(role, permission) {
    return ROLE_PERMISSIONS[role]?.[permission] === true;
}

// ───────────────────────────────────────────────────────────────
//  SESSION MANAGEMENT
// ───────────────────────────────────────────────────────────────

function getCurrentSession() {
    const session = loadData('ew_current_session');
    if (!session) return null;

    if (new Date(session.expiresAt) < new Date()) {
        logout();
        return null;
    }
    return session;
}

function createSession(user) {
    const session = {
        userId: user.id,
        username: user.username,
        role: user.role,
        expiresAt: new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString()
    };

    saveData('ew_current_session', session);
    updateUserLastLogin(user.id);
    return session;
}

function requireAuth() {
    const session = getCurrentSession();
    if (!session) {
        window.location.href = 'login.html';
        return null;
    }
    return session;
}

function logout() {
    localStorage.removeItem('ew_current_session');
    window.location.href = 'login.html';
}

// ───────────────────────────────────────────────────────────────
//  LOGIN
// ───────────────────────────────────────────────────────────────

function login(username, password) {
    const accounts = loadData('ew_accounts', []);

    const user = accounts.find(acc =>
        acc.username === username &&
        acc.password === simpleHash(password) &&
        acc.isActive
    );

    if (!user) {
        return { success: false, message: 'Invalid username or password' };
    }

    createSession(user);
    return { success: true };
}

// ───────────────────────────────────────────────────────────────
//  ACCOUNT MANAGEMENT (USED BY Account.html)
// ───────────────────────────────────────────────────────────────

function getAllAccounts() {
    return loadData('ew_accounts', []);
}

function createAccount(username, role, password, createdBy) {
    const accounts = loadData('ew_accounts', []);

    if (accounts.some(acc => acc.username === username)) {
        return { success: false, message: 'Username already exists' };
    }

    const newAccount = {
        id: generateId('user'),
        username,
        password: simpleHash(password),
        role,
        createdAt: new Date().toISOString(),
        createdBy,
        lastLogin: null,
        isActive: true
    };

    accounts.push(newAccount);
    saveData('ew_accounts', accounts);

    return { success: true, account: newAccount };
}

function updateAccount(accountId, updates) {
    const accounts = loadData('ew_accounts', []);
    const index = accounts.findIndex(acc => acc.id === accountId);

    if (index === -1) {
        return { success: false, message: 'Account not found' };
    }

    if (updates.password) {
        updates.password = simpleHash(updates.password);
    }

    accounts[index] = { ...accounts[index], ...updates };
    saveData('ew_accounts', accounts);

    return { success: true };
}

function deleteAccount(accountId) {
    const accounts = loadData('ew_accounts', []);
    const filtered = accounts.filter(acc => acc.id !== accountId);
    saveData('ew_accounts', filtered);
    return { success: true };
}

function updateUserLastLogin(userId) {
    const accounts = loadData('ew_accounts', []);
    const index = accounts.findIndex(acc => acc.id === userId);

    if (index !== -1) {
        accounts[index].lastLogin = new Date().toISOString();
        saveData('ew_accounts', accounts);
    }
}

// ───────────────────────────────────────────────────────────────
//  SYSTEM INITIALIZATION
// ───────────────────────────────────────────────────────────────

function initializeSystem() {
    const accounts = loadData('ew_accounts');
    if (accounts && accounts.length > 0) return;

    const superAdmin = {
        id: generateId('user'),
        username: 'admin',
        password: simpleHash('admin123'),
        role: 'Super Admin',
        createdAt: new Date().toISOString(),
        createdBy: null,
        lastLogin: null,
        isActive: true
    };

    saveData('ew_accounts', [superAdmin]);
    saveData('ew_daily_records', []);

    console.log('✅ System initialized');
    console.log('Default login: admin / admin123');
}

// Initialize on first load
if (typeof window !== 'undefined') {
    initializeSystem();
}
