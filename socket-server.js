const { Server } = require('socket.io');
const http = require('http');
const Redis = require('ioredis');

const redis = new Redis({
    host: process.env.REDIS_HOST || '127.0.0.1',
    port: process.env.REDIS_PORT || 6379,
});

const server = http.createServer();
const io = new Server(server, {
    cors: {
        origin: process.env.APP_URL || "http://localhost:8000",
        methods: ["GET", "POST"]
    }
});

// Stocker les connexions par élection
const electionRooms = new Map();

io.on('connection', (socket) => {
    console.log('Client connecté:', socket.id);

    // Rejoindre une salle d'élection
    socket.on('join-election', (electionId) => {
        socket.join(`election-${electionId}`);
        console.log(`Client ${socket.id} a rejoint l'élection ${electionId}`);
    });

    // Quitter une salle d'élection
    socket.on('leave-election', (electionId) => {
        socket.leave(`election-${electionId}`);
        console.log(`Client ${socket.id} a quitté l'élection ${electionId}`);
    });

    socket.on('disconnect', () => {
        console.log('Client déconnecté:', socket.id);
    });
});

// Écouter les événements Redis pour les mises à jour
const subscriber = redis.duplicate();
subscriber.subscribe('vote-updates');

subscriber.on('message', (channel, message) => {
    try {
        const data = JSON.parse(message);
        io.to(`election-${data.election_id}`).emit('vote-updated', data);
    } catch (error) {
        console.error('Erreur lors du traitement du message Redis:', error);
    }
});

const PORT = process.env.SOCKET_IO_PORT || 3000;
server.listen(PORT, () => {
    console.log(`Socket.io server running on port ${PORT}`);
});

