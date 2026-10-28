INSERT INTO GENRES (genre_id, genre_name) VALUES
(1, 'Action'),
(2, 'Sci-Fi'),
(3, 'Drama'),
(4, 'Adventure'),
(5, 'Crime'),
(6, 'Romance'),
(7, 'Fantasy'),
(8, 'Thriller'),
(9, 'Animation'),
(10, 'Comedy');

INSERT INTO MOVIES (movie_id, movie_title, movie_description, movie_duration, movie_release_date, movie_age_rating) VALUES
(1, 'Inception', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 148, '2010-07-16', 'PG-13'),
(2, 'The Dark Knight', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 152, '2008-07-18', 'PG-13'),
(3, 'Interstellar', 'When Earth becomes uninhabitable, a team of explorers travels through a wormhole in space in an attempt to ensure humanity''s survival.', 169, '2014-11-07', 'PG-13'),
(4, 'Pulp Fiction', 'The lives of two mob hitmen, a boxer, a gangster and his wife, and a pair of diner bandits intertwine in four tales of violence and redemption.', 154, '1994-10-14', 'R'),
(5, 'The Matrix', 'When a beautiful stranger leads computer hacker Neo to a forbidding underworld, he discovers the shocking truth--the life he knows is the elaborate deception of an evil cyber-intelligence.', 136, '1999-03-31', 'R'),
(6, 'Forrest Gump', 'The history of the United States from the 1950s to the 1970s unfolds from the perspective of an Alabama man with an IQ of 75, who yearns to be reunited with his childhood sweetheart.', 142, '1994-07-06', 'PG-13'),
(7, 'Gladiator', 'A former Roman General sets out to exact vengeance against the corrupt emperor who murdered his family and sent him into slavery.', 155, '2000-05-05', 'R'),
(8, 'Avatar', 'A paraplegic Marine dispatched to the moon Pandora on a unique mission becomes torn between following his orders and protecting the world he feels is his home.', 162, '2009-12-18', 'PG-13'),
(9, 'Parasite', 'Greed and class discrimination threaten the newly formed symbiotic relationship between the wealthy Park family and the destitute Kim clan.', 132, '2019-05-30', 'R'),
(10, 'Spirited Away', 'During her family''s move to the suburbs, a sullen 10-year-old girl wanders into a world ruled by gods, witches, and spirits, and where humans are changed into beasts.', 125, '2001-07-20', 'PG');

INSERT INTO MOVIE_GENRES (movie_id, genre_id) VALUES
-- Inception (Action, Sci-Fi, Adventure)
(1, 1), (1, 2), (1, 4),
-- The Dark Knight (Action, Crime, Drama)
(2, 1), (2, 5), (2, 3),
-- Interstellar (Sci-Fi, Adventure, Drama)
(3, 2), (3, 4), (3, 3),
-- Pulp Fiction (Crime, Drama)
(4, 5), (4, 3),
-- The Matrix (Action, Sci-Fi)
(5, 1), (5, 2),
-- Forrest Gump (Drama, Romance, Comedy)
(6, 3), (6, 6), (6, 10),
-- Gladiator (Action, Adventure, Drama)
(7, 1), (7, 4), (7, 3),
-- Avatar (Action, Sci-Fi, Fantasy)
(8, 1), (8, 2), (8, 7),
-- Parasite (Drama, Thriller, Comedy)
(9, 3), (9, 8), (9, 10),
-- Spirited Away (Animation, Fantasy, Adventure)
(10, 9), (10, 7), (10, 4);