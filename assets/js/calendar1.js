// Calendar View JavaScript

// API base path - use same as app5.js
// Since app5.js declares API_BASE with const (not global), we'll use the path directly
const CALENDAR_API = 'api/';

let calendarViewBtn;
let calendarModal;
let calendarView;

let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let calendarData = {};

// Function to open calendar (make it globally accessible)
window.openCalendar = function() {
    console.log('openCalendar called');
    
    // Update global references
    calendarModal = document.getElementById('calendarModal');
    calendarView = document.getElementById('calendarView');
    
    if (!calendarModal) {
        console.error('Calendar modal not found!');
        alert('Calendar modal not found. Please refresh the page.');
        return;
    }
    
    if (!calendarView) {
        console.error('Calendar view not found!');
        return;
    }
    
    console.log('Opening calendar modal');
    
    // Show modal first
    calendarModal.classList.add('show');
    
    // Then load calendar data
    loadCalendar();
    
    console.log('Modal show class added, modal classes:', calendarModal.className);
};

// Initialize calendar when DOM is ready
function initCalendar() {
    calendarViewBtn = document.getElementById('calendarViewBtn');
    calendarModal = document.getElementById('calendarModal');
    calendarView = document.getElementById('calendarView');
    
    if (!calendarViewBtn) {
        console.error('Calendar button not found');
        // Try again after a delay
        setTimeout(initCalendar, 200);
        return;
    }
    
    if (!calendarModal) {
        console.error('Calendar modal not found');
        return;
    }
    
    if (!calendarView) {
        console.error('Calendar view element not found');
        return;
    }
    
    // Open Calendar Modal - use both onclick and addEventListener for maximum compatibility
    calendarViewBtn.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        openCalendar();
    };
    
    calendarViewBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        openCalendar();
    });
    
    // Close button handler
    const closeBtn = calendarModal.querySelector('.close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            calendarModal.classList.remove('show');
        });
    }
    
    // Close modal when clicking outside
    calendarModal.addEventListener('click', (e) => {
        if (e.target === calendarModal) {
            calendarModal.classList.remove('show');
        }
    });
}

// Initialize when DOM is ready - try multiple times
function tryInit() {
    if (document.getElementById('calendarViewBtn')) {
        initCalendar();
    } else {
        setTimeout(tryInit, 100);
    }
}

// Start initialization
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tryInit);
} else {
    tryInit();
}

// Also try after window load as a fallback
window.addEventListener('load', () => {
    if (!calendarViewBtn) {
        setTimeout(tryInit, 100);
    }
});

// Load Calendar Data
async function loadCalendar() {
    // Ensure calendarView is set
    if (!calendarView) {
        calendarView = document.getElementById('calendarView');
    }
    
    if (!calendarView) {
        console.error('Calendar view element not found in loadCalendar');
        return;
    }
    
    // Render calendar immediately with empty data (no loading state)
    calendarData = {};
    // Initialize calendarData structure
    renderCalendar();
    
    // Then try to load data from API
    try {
        // Get the selected group filter value
        const groupFilter = document.getElementById('groupFilter');
        const groupId = groupFilter ? groupFilter.value : '';
        
        // Build URL with group_id parameter if a group is selected
        let url = `${CALENDAR_API}orders.php?action=calendar&month=${currentMonth}&year=${currentYear}`;
        if (groupId) {
            url += `&group_id=${groupId}`;
        }
        
        console.log('Loading calendar data from:', url);
        
        const response = await fetch(url);
        
        // Check if response is ok
        if (!response.ok) {
            console.warn('HTTP error! status:', response.status);
            return; // Keep the empty calendar
        }
        
        // Get response text first to check if it's valid JSON
        const responseText = await response.text();
        console.log('Response text:', responseText.substring(0, 200)); // Log first 200 chars
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Failed to parse JSON:', parseError);
            console.error('Response was:', responseText);
            return; // Keep the empty calendar
        }
        
        console.log('Calendar data received:', data);
        
        if (data.success && data.calendar && Array.isArray(data.calendar)) {
            calendarData = {};
            data.calendar.forEach(item => {
                calendarData[item.order_date] = {
                    count: item.count || 0,
                    tags: item.tags || []
                };
            });
            // Re-render with data
            renderCalendar();
        } else {
            console.warn('Calendar API returned unexpected format:', data);
        }
    } catch (error) {
        console.error('Error loading calendar data:', error);
        // Calendar is already rendered empty, so just log the error
    }
}

// Render Calendar
function renderCalendar() {
    console.log('renderCalendar called');
    
    // Ensure calendarView is set
    if (!calendarView) {
        calendarView = document.getElementById('calendarView');
    }
    
    if (!calendarView) {
        console.error('Calendar view element not found in renderCalendar');
        return;
    }
    
    console.log('Rendering calendar for month:', currentMonth, 'year:', currentYear);
    
    try {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
        const today = new Date();
    
    let html = `
        <div class="calendar-header">
            <h3>${monthNames[currentMonth - 1]} ${currentYear}</h3>
            <div class="calendar-nav">
                <button class="btn btn-secondary btn-sm" onclick="changeMonth(-1)">← Previous</button>
                <button class="btn btn-secondary btn-sm" onclick="changeMonth(1)">Next →</button>
            </div>
        </div>
        <div class="calendar-grid">
            <div class="calendar-day-header">Sun</div>
            <div class="calendar-day-header">Mon</div>
            <div class="calendar-day-header">Tue</div>
            <div class="calendar-day-header">Wed</div>
            <div class="calendar-day-header">Thu</div>
            <div class="calendar-day-header">Fri</div>
            <div class="calendar-day-header">Sat</div>
    `;
    
    // Empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        html += '<div class="calendar-day"></div>';
    }
    
    // Days of month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dateData = calendarData[dateStr] || { count: 0, tags: [] };
        const count = dateData.count || 0;
        const tags = dateData.tags || [];
        const isToday = today.getFullYear() === currentYear && 
                        today.getMonth() + 1 === currentMonth && 
                        today.getDate() === day;
        
        let classes = 'calendar-day';
        if (count > 0) classes += ' has-orders';
        if (isToday) classes += ' today';
        
        // Helper function to get contrast color
        const getContrastColor = (hexColor) => {
            const hex = hexColor.replace('#', '');
            const r = parseInt(hex.substr(0, 2), 16);
            const g = parseInt(hex.substr(2, 2), 16);
            const b = parseInt(hex.substr(4, 2), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            return luminance > 0.5 ? '#000000' : '#ffffff';
        };
        
        // Render tags (max 2)
        let tagsHtml = '';
        if (tags.length > 0) {
            const tagsToShow = tags.slice(0, 2);
            tagsHtml = '<div class="calendar-day-tags">';
            tagsToShow.forEach(tag => {
                const tagName = typeof tag === 'string' ? tag : (tag.name || '');
                const tagColor = typeof tag === 'string' ? '#4CAF50' : (tag.color || '#4CAF50');
                const textColor = getContrastColor(tagColor);
                tagsHtml += `<span class="calendar-tag" style="background-color: ${tagColor}; color: ${textColor};">${tagName}</span>`;
            });
            tagsHtml += '</div>';
        }
        
        html += `
            <div class="${classes}" onclick="viewDateOrders('${dateStr}')">
                <div class="calendar-day-number">${day}</div>
                ${count > 0 ? `<div class="calendar-day-count">${count} order${count > 1 ? 's' : ''}</div>` : ''}
                ${tagsHtml}
            </div>
        `;
    }
    
    html += '</div>';
    
    console.log('Setting calendar HTML, length:', html.length);
    calendarView.innerHTML = html;
    console.log('Calendar rendered successfully');
    } catch (error) {
        console.error('Error in renderCalendar:', error);
        if (calendarView) {
            calendarView.innerHTML = '<div style="text-align: center; padding: 40px;"><p>Error rendering calendar. Please refresh the page.</p></div>';
        }
    }
}

// Change Month (make it globally accessible for onclick handlers)
window.changeMonth = function(delta) {
    currentMonth += delta;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    loadCalendar();
};

// Make viewDateOrders globally accessible
window.viewDateOrders = function(date) {
    if (calendarModal) {
        calendarModal.classList.remove('show');
    }
    
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        dateFilter.value = date;
    }
    
    // Call loadOrders if it exists (from app5.js)
    if (typeof loadOrders === 'function') {
        loadOrders();
    }
};


