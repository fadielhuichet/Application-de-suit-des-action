CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    nom VARCHAR(50),
    prenom VARCHAR(50),
    poste VARCHAR(50),
    role VARCHAR(20)
);

CREATE TABLE constat (
    id SERIAL PRIMARY KEY,
    date DATE NOT NULL,
    user_id INT NOT NULL REFERENCES users(id),
    observation TEXT,
    action_propose TEXT,
    etat VARCHAR(20),
    date_traitement DATE,
    action_faite TEXT,
    type_id INT REFERENCES type_constat(id)
);