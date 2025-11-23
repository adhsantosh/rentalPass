// server.js — FINAL FIXED VERSION (ESM)
import { createServer } from 'http';
import { Server } from 'socket.io';

const httpServer = createServer();
const io = new Server(httpServer, {
    cors: {
        origin: "*", // Change to your domain in production
        methods: ["GET", "POST"]
    }
});

const PORT = 3000;
const activeUsers = new Set();

io.on('connection', (socket) => {
    console.log('Client connected:', socket.id);

    // USER JOINS → joins their own room (CRITICAL!)
    socket.on('join', (userId) => {
        const id = String(userId); // Always string!
        socket.join(id);                    // ← THIS WAS MISSING BEFORE!
        socket.userId = id;
        activeUsers.add(id);
        console.log(`User ${id} joined (online)`);
        io.emit('user-list-update', Array.from(activeUsers));
    });

    // ADMIN JOINS
    socket.on('join-admin', () => {
        socket.join('admin');
        console.log('Admin connected');
        socket.emit('user-list', Array.from(activeUsers));
    });

    // MESSAGE HANDLING — NOW WORKS BOTH WAYS
    socket.on('send-message', (data) => {
        const { from, to, message, senderType } = data;

        // Save to DB
        fetch('http://localhost/rental_pass/save_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `from=${from}&to=${to}&message=${encodeURIComponent(message)}&type=${senderType}`
        }).catch(err => console.error('DB save failed:', err));

        if (to === 'admin') {
            // User → Admin
            io.to('admin').emit('new-message', {
                from,
                message,
                senderType: 'user'
            });
        } else {
            // Admin → User (NOW WORKS!)
            const userRoom = String(to);
            io.to(userRoom).emit('new-message', {
                from: 'admin',
                message,
                senderType: 'admin'
            });

            // Also send back to admin (for their chat history)
            io.to('admin').emit('new-message', {
                from,
                to,
                message,
                senderType: 'admin'
            });
        }
    });

    socket.on('disconnect', () => {
        if (socket.userId && socket.userId !== 'admin') {
            activeUsers.delete(socket.userId);
            console.log(`User ${socket.userId} went offline`);
            io.emit('user-list-update', Array.from(activeUsers));
        }
    });
});

httpServer.listen(PORT, () => {
    console.log(`Real-time server running on http://localhost:${PORT}`);
    console.log(`Total online users: ${activeUsers.size}`);
});