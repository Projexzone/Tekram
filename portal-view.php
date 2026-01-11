<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tekram Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
</head>
<body>
    <div id="app" class="min-h-screen bg-gray-100">
        <!-- Loading State -->
        <div v-if="loading" class="flex items-center justify-center h-screen">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Loading...</p>
            </div>
        </div>

        <!-- Main Portal -->
        <div v-else class="flex">
            <!-- Sidebar -->
            <div :class="sidebarOpen ? 'w-64' : 'w-20'" class="bg-gradient-to-b from-blue-600 to-blue-800 text-white transition-all duration-300 flex flex-col min-h-screen">
                <!-- Logo -->
                <div class="p-6 flex items-center justify-between">
                    <h1 v-if="sidebarOpen" class="text-2xl font-bold">TEKRAM</h1>
                    <button @click="sidebarOpen = !sidebarOpen" class="p-2 hover:bg-blue-700 rounded-lg">
                        <span v-if="sidebarOpen">✕</span>
                        <span v-else>☰</span>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 space-y-2">
                    <button 
                        v-for="item in navigation" 
                        :key="item.id"
                        @click="activeView = item.id"
                        :class="activeView === item.id ? 'bg-blue-700' : 'hover:bg-blue-700/50'"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors">
                        <span class="text-xl">{{ item.icon }}</span>
                        <span v-if="sidebarOpen" class="font-medium">{{ item.name }}</span>
                    </button>
                </nav>

                <!-- Logout -->
                <div class="p-3 border-t border-blue-700">
                    <button @click="logout" class="w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        <span class="text-xl">🚪</span>
                        <span v-if="sidebarOpen" class="font-medium">Logout</span>
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                <header class="bg-white shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">{{ currentViewName }}</h2>
                            <p class="text-gray-500 text-sm mt-1">Welcome back to your market management portal</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <p class="font-medium text-gray-800">{{ currentUser.name }}</p>
                                <p class="text-sm text-gray-500">{{ currentUser.email }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold">
                                {{ currentUser.initials }}
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Content Area -->
                <main class="flex-1 p-6 overflow-auto">
                    <!-- Dashboard View -->
                    <div v-if="activeView === 'dashboard'" class="space-y-6">
                        <!-- Stats Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Total Vendors</p>
                                        <p class="text-3xl font-bold mt-2">{{ stats.totalVendors }}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-blue-500">
                                        <span class="text-white text-2xl">👥</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Active Bookings</p>
                                        <p class="text-3xl font-bold mt-2">{{ stats.activeBookings }}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-green-500">
                                        <span class="text-white text-2xl">📍</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Upcoming Markets</p>
                                        <p class="text-3xl font-bold mt-2">{{ stats.upcomingEvents }}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-purple-500">
                                        <span class="text-white text-2xl">📅</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-gray-500 text-sm font-medium">Monthly Revenue</p>
                                        <p class="text-3xl font-bold mt-2">${{ formatNumber(stats.revenue) }}</p>
                                    </div>
                                    <div class="p-3 rounded-lg bg-orange-500">
                                        <span class="text-white text-2xl">💰</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Upcoming Markets & Recent Bookings -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-lg shadow p-6">
                                <h3 class="text-lg font-semibold mb-4">Upcoming Markets</h3>
                                <div class="space-y-3">
                                    <div v-for="event in events.slice(0, 3)" :key="event.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div>
                                            <p class="font-medium">{{ event.name }}</p>
                                            <p class="text-sm text-gray-500">{{ event.date }} • {{ event.vendors }} vendors</p>
                                        </div>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                            {{ event.status }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-lg shadow p-6">
                                <h3 class="text-lg font-semibold mb-4">Recent Bookings</h3>
                                <div class="space-y-3">
                                    <div v-for="booking in bookings.slice(0, 3)" :key="booking.id" 
                                         class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div class="flex-1">
                                            <p class="font-medium">{{ booking.vendor }}</p>
                                            <p class="text-sm text-gray-500">{{ booking.event }} - Site {{ booking.site }}</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold">${{ booking.amount }}</p>
                                            <span>{{ booking.status === 'confirmed' ? '✅' : '⏱️' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Markets View -->
                    <div v-else-if="activeView === 'events'" class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                            <h2 class="text-xl font-semibold">Markets</h2>
                            <button @click="createMarket" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                + Create Market
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Market Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendors</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="event in events" :key="event.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap font-medium">{{ event.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-gray-600">{{ event.date }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ event.vendors }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">
                                                {{ event.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button @click="editEvent(event.id)" class="text-blue-600 hover:text-blue-800 mr-3">Edit</button>
                                            <button @click="viewEvent(event.id)" class="text-gray-600 hover:text-gray-800">View</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Other Views -->
                    <div v-else class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold">{{ currentViewName }}</h2>
                        <p class="text-gray-500 mt-2">Coming soon...</p>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <script>
        const { createApp } = Vue;

        createApp({
            data() {
                return {
                    activeView: 'dashboard',
                    sidebarOpen: true,
                    loading: true,
                    stats: {
                        totalVendors: 0,
                        activeBookings: 0,
                        upcomingEvents: 0,
                        revenue: 0
                    },
                    events: [],
                    bookings: [],
                    navigation: [
                        { id: 'dashboard', name: 'Dashboard', icon: '📊' },
                        { id: 'events', name: 'Markets', icon: '📅' },
                        { id: 'vendors', name: 'Vendors', icon: '👥' },
                        { id: 'bookings', name: 'Bookings', icon: '📍' },
                        { id: 'revenue', name: 'Revenue', icon: '💰' },
                        { id: 'settings', name: 'Settings', icon: '⚙️' }
                    ],
                    currentUser: {
                        name: 'Market Manager',
                        email: 'admin@market.com',
                        initials: 'MM'
                    }
                };
            },
            computed: {
                currentViewName() {
                    const view = this.navigation.find(n => n.id === this.activeView);
                    return view ? view.name : 'Dashboard';
                }
            },
            methods: {
                async loadData() {
                    this.loading = true;
                    try {
                        // Replace with actual API calls
                        const response = await fetch(`${window.tekramPortal?.restUrl || '/wp-json/tekram/v1/'}dashboard/stats`, {
                            headers: {
                                'X-WP-Nonce': window.tekramPortal?.nonce || ''
                            }
                        });
                        
                        if (response.ok) {
                            this.stats = await response.json();
                        }

                        // Load events
                        const eventsResponse = await fetch(`${window.tekramPortal?.restUrl || '/wp-json/tekram/v1/'}events`, {
                            headers: {
                                'X-WP-Nonce': window.tekramPortal?.nonce || ''
                            }
                        });
                        
                        if (eventsResponse.ok) {
                            this.events = await eventsResponse.json();
                        }

                        // Load bookings
                        const bookingsResponse = await fetch(`${window.tekramPortal?.restUrl || '/wp-json/tekram/v1/'}bookings`, {
                            headers: {
                                'X-WP-Nonce': window.tekramPortal?.nonce || ''
                            }
                        });
                        
                        if (bookingsResponse.ok) {
                            this.bookings = await bookingsResponse.json();
                        }
                    } catch (error) {
                        console.error('Error loading data:', error);
                        // Use dummy data for demo
                        this.stats = {
                            totalVendors: 245,
                            activeBookings: 89,
                            upcomingEvents: 12,
                            revenue: 15840
                        };
                        this.events = [
                            { id: 1, name: 'Summer Night Market', date: '2026-01-15', vendors: 45, status: 'upcoming' },
                            { id: 2, name: 'Farmers Market', date: '2026-01-18', vendors: 32, status: 'upcoming' },
                            { id: 3, name: 'Craft Fair', date: '2026-01-22', vendors: 28, status: 'upcoming' }
                        ];
                        this.bookings = [
                            { id: 1, vendor: 'Fresh Produce Co', event: 'Summer Night Market', site: 'A-12', status: 'confirmed', amount: 150 },
                            { id: 2, vendor: 'Handmade Jewelry', event: 'Craft Fair', site: 'B-05', status: 'pending', amount: 120 },
                            { id: 3, vendor: 'Food Truck Express', event: 'Farmers Market', site: 'C-08', status: 'confirmed', amount: 200 }
                        ];
                    }
                    this.loading = false;
                },
                formatNumber(num) {
                    return num ? num.toLocaleString() : '0';
                },
                createMarket() {
                    alert('Create market form - coming soon!');
                },
                editEvent(id) {
                    alert(`Edit event ${id} - coming soon!`);
                },
                viewEvent(id) {
                    alert(`View event ${id} - coming soon!`);
                },
                logout() {
                    window.location.href = window.tekramPortal?.logoutUrl || '/wp-login.php?action=logout';
                }
            },
            mounted() {
                // Set current user from WordPress
                if (window.tekramPortal?.currentUser) {
                    this.currentUser = {
                        ...window.tekramPortal.currentUser,
                        initials: window.tekramPortal.currentUser.name.substring(0, 2).toUpperCase()
                    };
                }
                
                this.loadData();
            }
        }).mount('#app');
    </script>
</body>
</html>
