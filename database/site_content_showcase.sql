INSERT INTO site_settings (
    settings_id,
    company_description,
    contact_address,
    contact_phone,
    contact_email
) VALUES (
    1,
    'We wanted to create a different kind of cinema experience. Instead of spending forever deciding what to watch, what if the cinema could make the choice for you? That idea became Mystery Cinema: a place where you can book a film the normal way or hand over control and let the surprise be part of the experience.',
    'Spangsbjergvej 1, 6700 Esbjerg, Denmark',
    '+45 70 12 34 56',
    'hej@mysterycinema.dk'
);

INSERT INTO site_opening_hours (day_of_week, opens_at, closes_at, is_closed) VALUES
(1, '13:00:00', '23:00:00', 0),
(2, '13:00:00', '23:00:00', 0),
(3, '13:00:00', '23:00:00', 0),
(4, '13:00:00', '23:00:00', 0),
(5, '12:00:00', '01:00:00', 0),
(6, '12:00:00', '01:00:00', 0),
(7, '12:00:00', '01:00:00', 0);