CREATE TABLE constats (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    user_id INT NOT NULL REFERENCES "user"(id),
    observation TEXT,
    action_propose TEXT,
    etat VARCHAR(20) DEFAULT 'En attente',
    date_traitement DATE,
    action_faite TEXT,
    id_type INT

);