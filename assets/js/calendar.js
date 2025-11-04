// Calendar View JavaScript

const calendarViewBtn = document.getElementById('calendarViewBtn');
const calendarModal = document.getElementById('calendarModal');
const calendarView = document.getElementById('calendarView');

let currentMonth = new Date().getMonth() + 1;
let currentYear = new Date().getFullYear();
let calendarData = {};

// Open Calendar Modal
calendarViewBtn.addEventListener('click', () => {
    loadCalendar();
    calendarModal.classList.add('show');
});

// Load Calendar Data
async function loadCalendar() {
    try {
        const response = await fetch(`${API_BASE}orders.php?action=calendar&month=${currentMonth}&year=${currentYear}`);
        const data = await response.json();
        
        if (data.success) {
            calendarData = {};
            data.calendar.forEach(item => {
                calendarData[item.order_date] = item.count;
            });
            renderCalendar();
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
    }
}

// Render Calendar
function renderCalendar() {
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
        const count = calendarData[dateStr] || 0;
        const isToday = today.getFullYear() === currentYear && 
                        today.getMonth() + 1 === currentMonth && 
                        today.getDate() === day;
        
        let classes = 'calendar-day';
        if (count > 0) classes += ' has-orders';
        if (isToday) classes += ' today';
        
        html += `
            <div class="${classes}" onclick="viewDateOrders('${dateStr}')">
                <div class="calendar-day-number">${day}</div>
                ${count > 0 ? `<div class="calendar-day-count">${count} order${count > 1 ? 's' : ''}</div>` : ''}
            </div>
        `;
    }
    
    html += '</div>';
    calendarView.innerHTML = html;
}

// Change Month
function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    loadCalendar();
}

// View Orders for Specific Date
function viewDateOrders(date) {
    calendarModal.classList.remove('show');
    dateFilter.value = date;
    loadOrders();
}

