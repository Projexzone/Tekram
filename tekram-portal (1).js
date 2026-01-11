// assets/js/portal.js
// Compile your React TSX to this file using Babel or TypeScript

const { useState, useEffect } = React;
const { BarChart3, Users, Calendar, DollarSign, MapPin, Settings, LogOut, Menu, X, CheckCircle, Clock } = window.lucide || {};

const TekramPortal = () => {
  const [activeView, setActiveView] = useState('dashboard');
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [loading, setLoading] = useState(false);
  const [stats, setStats] = useState({
    totalVendors: 0,
    activeBookings: 0,
    upcomingEvents: 0,
    revenue: 0
  });
  const [events, setEvents] = useState([]);
  const [bookings, setBookings] = useState([]);
  const [vendors, setVendors] = useState([]);

  // API Helper
  const apiCall = async (endpoint, method = 'GET', body = null) => {
    const url = `${tekramPortal.restUrl}${endpoint}`;
    const options = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': tekramPortal.nonce
      }
    };

    if (body) {
      options.body = JSON.stringify(body);
    }

    const response = await fetch(url, options);
    return response.json();
  };

  // Load dashboard data
  useEffect(() => {
    loadDashboardData();
  }, []);

  const loadDashboardData = async () => {
    setLoading(true);
    try {
      const [statsData, eventsData, bookingsData] = await Promise.all([
        apiCall('dashboard/stats'),
        apiCall('events'),
        apiCall('bookings')
      ]);

      setStats(statsData);
      setEvents(eventsData);
      setBookings(bookingsData);
    } catch (error) {
      console.error('Error loading dashboard:', error);
    }
    setLoading(false);
  };

  const navigation = [
    { id: 'dashboard', name: 'Dashboard', icon: '📊' },
    { id: 'events', name: 'Markets', icon: '📅' },
    { id: 'vendors', name: 'Vendors', icon: '👥' },
    { id: 'bookings', name: 'Bookings', icon: '📍' },
    { id: 'revenue', name: 'Revenue', icon: '💰' },
    { id: 'settings', name: 'Settings', icon: '⚙️' }
  ];

  const StatCard = ({ title, value, icon, color }) => (
    React.createElement('div', { className: 'bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow' },
      React.createElement('div', { className: 'flex items-center justify-between' },
        React.createElement('div', null,
          React.createElement('p', { className: 'text-gray-500 text-sm font-medium' }, title),
          React.createElement('p', { className: 'text-3xl font-bold mt-2' }, value)
        ),
        React.createElement('div', { className: `p-3 rounded-lg ${color}` },
          React.createElement('span', { className: 'text-white text-2xl' }, icon)
        )
      )
    )
  );

  const DashboardView = () => (
    React.createElement('div', { className: 'space-y-6' },
      React.createElement('div', { className: 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6' },
        React.createElement(StatCard, { title: 'Total Vendors', value: stats.totalVendors, icon: '👥', color: 'bg-blue-500' }),
        React.createElement(StatCard, { title: 'Active Bookings', value: stats.activeBookings, icon: '📍', color: 'bg-green-500' }),
        React.createElement(StatCard, { title: 'Upcoming Markets', value: stats.upcomingEvents, icon: '📅', color: 'bg-purple-500' }),
        React.createElement(StatCard, { title: 'Monthly Revenue', value: `$${stats.revenue?.toLocaleString() || 0}`, icon: '💰', color: 'bg-orange-500' })
      ),
      React.createElement('div', { className: 'grid grid-cols-1 lg:grid-cols-2 gap-6' },
        React.createElement('div', { className: 'bg-white rounded-lg shadow p-6' },
          React.createElement('h3', { className: 'text-lg font-semibold mb-4' }, 'Upcoming Markets'),
          React.createElement('div', { className: 'space-y-3' },
            events.slice(0, 3).map(event =>
              React.createElement('div', { key: event.id, className: 'flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors' },
                React.createElement('div', null,
                  React.createElement('p', { className: 'font-medium' }, event.name),
                  React.createElement('p', { className: 'text-sm text-gray-500' }, `${event.date} • ${event.vendors} vendors`)
                ),
                React.createElement('span', { className: 'px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium' }, event.status)
              )
            )
          )
        ),
        React.createElement('div', { className: 'bg-white rounded-lg shadow p-6' },
          React.createElement('h3', { className: 'text-lg font-semibold mb-4' }, 'Recent Bookings'),
          React.createElement('div', { className: 'space-y-3' },
            bookings.slice(0, 3).map(booking =>
              React.createElement('div', { key: booking.id, className: 'flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors' },
                React.createElement('div', { className: 'flex-1' },
                  React.createElement('p', { className: 'font-medium' }, booking.vendor),
                  React.createElement('p', { className: 'text-sm text-gray-500' }, `${booking.event} - Site ${booking.site}`)
                ),
                React.createElement('div', { className: 'text-right' },
                  React.createElement('p', { className: 'font-semibold' }, `$${booking.amount}`),
                  React.createElement('span', null, booking.status === 'confirmed' ? '✅' : '⏱️')
                )
              )
            )
          )
        )
      )
    )
  );

  const EventsView = () => (
    React.createElement('div', { className: 'bg-white rounded-lg shadow' },
      React.createElement('div', { className: 'p-6 border-b border-gray-200 flex justify-between items-center' },
        React.createElement('h2', { className: 'text-xl font-semibold' }, 'Markets'),
        React.createElement('button', {
          className: 'px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors',
          onClick: () => alert('Create market form coming soon!')
        }, '+ Create Market')
      ),
      React.createElement('div', { className: 'overflow-x-auto' },
        React.createElement('table', { className: 'w-full' },
          React.createElement('thead', { className: 'bg-gray-50' },
            React.createElement('tr', null,
              React.createElement('th', { className: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase' }, 'Market Name'),
              React.createElement('th', { className: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase' }, 'Date'),
              React.createElement('th', { className: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase' }, 'Vendors'),
              React.createElement('th', { className: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase' }, 'Status'),
              React.createElement('th', { className: 'px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase' }, 'Actions')
            )
          ),
          React.createElement('tbody', { className: 'divide-y divide-gray-200' },
            events.map(event =>
              React.createElement('tr', { key: event.id, className: 'hover:bg-gray-50' },
                React.createElement('td', { className: 'px-6 py-4 whitespace-nowrap font-medium' }, event.name),
                React.createElement('td', { className: 'px-6 py-4 whitespace-nowrap text-gray-600' }, event.date),
                React.createElement('td', { className: 'px-6 py-4 whitespace-nowrap' }, event.vendors),
                React.createElement('td', { className: 'px-6 py-4 whitespace-nowrap' },
                  React.createElement('span', { className: 'px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm' }, event.status)
                ),
                React.createElement('td', { className: 'px-6 py-4 whitespace-nowrap' },
                  React.createElement('button', { className: 'text-blue-600 hover:text-blue-800 mr-3' }, 'Edit'),
                  React.createElement('button', { className: 'text-gray-600 hover:text-gray-800' }, 'View')
                )
              )
            )
          )
        )
      )
    )
  );

  const renderContent = () => {
    if (loading) {
      return React.createElement('div', { className: 'flex items-center justify-center h-64' },
        React.createElement('div', { className: 'text-center' },
          React.createElement('div', { className: 'animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto' }),
          React.createElement('p', { className: 'mt-4 text-gray-600' }, 'Loading...')
        )
      );
    }

    switch (activeView) {
      case 'dashboard': return React.createElement(DashboardView);
      case 'events': return React.createElement(EventsView);
      case 'vendors':
      case 'bookings':
      case 'revenue':
      case 'settings':
        return React.createElement('div', { className: 'bg-white rounded-lg shadow p-6' },
          React.createElement('h2', { className: 'text-xl font-semibold' }, navigation.find(n => n.id === activeView)?.name),
          React.createElement('p', { className: 'text-gray-500 mt-2' }, 'Coming soon...')
        );
      default: return React.createElement(DashboardView);
    }
  };

  return React.createElement('div', { className: 'min-h-screen bg-gray-100 flex' },
    // Sidebar
    React.createElement('div', { className: `${sidebarOpen ? 'w-64' : 'w-20'} bg-gradient-to-b from-blue-600 to-blue-800 text-white transition-all duration-300 flex flex-col` },
      React.createElement('div', { className: 'p-6 flex items-center justify-between' },
        sidebarOpen && React.createElement('h1', { className: 'text-2xl font-bold' }, 'TEKRAM'),
        React.createElement('button', {
          onClick: () => setSidebarOpen(!sidebarOpen),
          className: 'p-2 hover:bg-blue-700 rounded-lg'
        }, sidebarOpen ? '✕' : '☰')
      ),
      React.createElement('nav', { className: 'flex-1 px-3 space-y-2' },
        navigation.map(item =>
          React.createElement('button', {
            key: item.id,
            onClick: () => setActiveView(item.id),
            className: `w-full flex items-center gap-3 px-4 py-3 rounded-lg transition-colors ${activeView === item.id ? 'bg-blue-700' : 'hover:bg-blue-700/50'}`
          },
            React.createElement('span', { className: 'text-xl' }, item.icon),
            sidebarOpen && React.createElement('span', { className: 'font-medium' }, item.name)
          )
        )
      ),
      React.createElement('div', { className: 'p-3 border-t border-blue-700' },
        React.createElement('button', {
          className: 'w-full flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-700 transition-colors',
          onClick: () => window.location.href = tekramPortal.logoutUrl || '/wp-login.php?action=logout'
        },
          React.createElement('span', { className: 'text-xl' }, '🚪'),
          sidebarOpen && React.createElement('span', { className: 'font-medium' }, 'Logout')
        )
      )
    ),
    // Main Content
    React.createElement('div', { className: 'flex-1 flex flex-col' },
      // Header
      React.createElement('header', { className: 'bg-white shadow-sm p-6' },
        React.createElement('div', { className: 'flex items-center justify-between' },
          React.createElement('div', null,
            React.createElement('h2', { className: 'text-2xl font-bold text-gray-800' },
              navigation.find(n => n.id === activeView)?.name || 'Dashboard'
            ),
            React.createElement('p', { className: 'text-gray-500 text-sm mt-1' }, 'Welcome back to your market management portal')
          ),
          React.createElement('div', { className: 'flex items-center gap-4' },
            React.createElement('div', { className: 'text-right' },
              React.createElement('p', { className: 'font-medium text-gray-800' }, tekramPortal.currentUser.name),
              React.createElement('p', { className: 'text-sm text-gray-500' }, tekramPortal.currentUser.email)
            ),
            React.createElement('div', { className: 'w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-semibold' },
              tekramPortal.currentUser.name.substring(0, 2).toUpperCase()
            )
          )
        )
      ),
      // Content
      React.createElement('main', { className: 'flex-1 p-6 overflow-auto' },
        renderContent()
      )
    )
  );
};

// Mount React app
document.addEventListener('DOMContentLoaded', () => {
  const root = document.getElementById('tekram-portal-root');
  if (root && typeof ReactDOM !== 'undefined') {
    ReactDOM.render(React.createElement(TekramPortal), root);
  }
});
