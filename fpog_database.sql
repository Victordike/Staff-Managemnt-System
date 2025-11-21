--
-- PostgreSQL database dump
--

\restrict KPrLwnCGRSBa9Bifn4AagUAV5YbnfQaVU2IqX3IOaCMn8tMUgmx4wnHGppqGElJ

-- Dumped from database version 16.9 (415ebe8)
-- Dumped by pg_dump version 16.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: admin_roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_roles (
    id integer NOT NULL,
    admin_id integer NOT NULL,
    role_name character varying(100) NOT NULL,
    assigned_by integer NOT NULL,
    assigned_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    removed_at timestamp without time zone
);


--
-- Name: admin_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_roles_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_roles_id_seq OWNED BY public.admin_roles.id;


--
-- Name: admin_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.admin_users (
    id integer NOT NULL,
    staff_id character varying(50) NOT NULL,
    surname character varying(100) NOT NULL,
    firstname character varying(100) NOT NULL,
    othername character varying(100),
    date_of_birth date NOT NULL,
    sex character varying(10) NOT NULL,
    marital_status character varying(20) NOT NULL,
    permanent_home_address text NOT NULL,
    lga_origin character varying(100) NOT NULL,
    department character varying(100) NOT NULL,
    "position" character varying(100) NOT NULL,
    type_of_employment character varying(50) NOT NULL,
    date_of_assumption date NOT NULL,
    cadre character varying(100) NOT NULL,
    salary_structure character varying(100),
    gl character varying(50),
    step character varying(50),
    rank character varying(100),
    phone_number character varying(20) NOT NULL,
    official_email character varying(255) NOT NULL,
    bank_name character varying(100) NOT NULL,
    account_name character varying(100) NOT NULL,
    account_number character varying(20) NOT NULL,
    pfa_name character varying(100) NOT NULL,
    pfa_pin character varying(50) NOT NULL,
    nok_fullname character varying(200) NOT NULL,
    nok_phone_number character varying(20) NOT NULL,
    nok_relationship character varying(50) NOT NULL,
    nok_address text NOT NULL,
    password character varying(255) NOT NULL,
    profile_picture character varying(255),
    is_active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: admin_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.admin_users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: admin_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.admin_users_id_seq OWNED BY public.admin_users.id;


--
-- Name: memo_recipients; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.memo_recipients (
    id integer NOT NULL,
    memo_id integer NOT NULL,
    admin_id integer NOT NULL,
    read_at timestamp without time zone,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: memo_recipients_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.memo_recipients_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: memo_recipients_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.memo_recipients_id_seq OWNED BY public.memo_recipients.id;


--
-- Name: memos; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.memos (
    id integer NOT NULL,
    sender_id integer NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    file_path character varying(500) NOT NULL,
    file_type character varying(255) NOT NULL,
    recipient_type character varying(20) NOT NULL,
    recipient_id integer,
    blur_detected boolean DEFAULT false,
    status character varying(20) DEFAULT 'active'::character varying,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: memos_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.memos_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: memos_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.memos_id_seq OWNED BY public.memos.id;


--
-- Name: pre_users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.pre_users (
    id integer NOT NULL,
    surname character varying(100) NOT NULL,
    firstname character varying(100) NOT NULL,
    othername character varying(100),
    staff_id character varying(50) NOT NULL,
    salary_structure character varying(100),
    gl character varying(50),
    step character varying(50),
    rank character varying(100),
    is_registered boolean DEFAULT false,
    uploaded_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: pre_users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.pre_users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pre_users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.pre_users_id_seq OWNED BY public.pre_users.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id integer NOT NULL,
    user_id integer NOT NULL,
    user_type character varying(20) NOT NULL,
    session_token character varying(255) NOT NULL,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    expires_at timestamp without time zone NOT NULL
);


--
-- Name: sessions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.sessions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: sessions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.sessions_id_seq OWNED BY public.sessions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id integer NOT NULL,
    username character varying(100) NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    firstname character varying(100) NOT NULL,
    lastname character varying(100) NOT NULL,
    role character varying(20) DEFAULT 'superadmin'::character varying,
    profile_picture character varying(255),
    created_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: admin_roles id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_roles ALTER COLUMN id SET DEFAULT nextval('public.admin_roles_id_seq'::regclass);


--
-- Name: admin_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_users ALTER COLUMN id SET DEFAULT nextval('public.admin_users_id_seq'::regclass);


--
-- Name: memo_recipients id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memo_recipients ALTER COLUMN id SET DEFAULT nextval('public.memo_recipients_id_seq'::regclass);


--
-- Name: memos id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memos ALTER COLUMN id SET DEFAULT nextval('public.memos_id_seq'::regclass);


--
-- Name: pre_users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pre_users ALTER COLUMN id SET DEFAULT nextval('public.pre_users_id_seq'::regclass);


--
-- Name: sessions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions ALTER COLUMN id SET DEFAULT nextval('public.sessions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: admin_roles; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.admin_roles (id, admin_id, role_name, assigned_by, assigned_at, removed_at) FROM stdin;
1	1	Academic Dean	1	2025-11-21 13:15:51.098933	\N
2	1	HOD Engineering	1	2025-11-21 13:16:22.421405	2025-11-21 13:17:21.739635
3	1	HOD Management	1	2025-11-21 13:16:22.421405	2025-11-21 13:17:21.739635
\.


--
-- Data for Name: admin_users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.admin_users (id, staff_id, surname, firstname, othername, date_of_birth, sex, marital_status, permanent_home_address, lga_origin, department, "position", type_of_employment, date_of_assumption, cadre, salary_structure, gl, step, rank, phone_number, official_email, bank_name, account_name, account_number, pfa_name, pfa_pin, nok_fullname, nok_phone_number, nok_relationship, nok_address, password, profile_picture, is_active, created_at, updated_at) FROM stdin;
1	FPOG/REG/PF/034	Dike	Victor		2002-11-18	Male	Single	Ndi-Uka Compound, Ngusu-Edda, Ebonny State	Afikpo-South	Computer Science	Lecturer	Temporary	2025-11-21	Academics				Senior Lecturer	07038642335	victordike019@fpog.edu.ng	United Bank of Africa	Victor Dike	2222821890	FCMB Pensions	3356	Gideon Dike	08163812172	Sibling	Back of Trumat Supermarket, King Jaja Street, Bonny	$2y$10$94gXD.0rnDB59E5b03LBJuIkUs9VWD0lGf9OzMQHCZPrtkMbKIpxW	uploads/profile_pictures/69207184ab1d1_1710064564171.jpg	t	2025-11-21 12:28:22.502503	2025-11-21 12:28:22.502503
\.


--
-- Data for Name: memo_recipients; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.memo_recipients (id, memo_id, admin_id, read_at, created_at) FROM stdin;
1	1	1	2025-11-21 14:40:45.337336	2025-11-21 14:35:17.41057
2	2	1	\N	2025-11-21 15:01:04.528327
3	3	1	\N	2025-11-21 15:01:29.984943
4	4	1	2025-11-21 15:03:23.424511	2025-11-21 15:02:31.052573
\.


--
-- Data for Name: memos; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.memos (id, sender_id, title, description, file_path, file_type, recipient_type, recipient_id, blur_detected, status, created_at) FROM stdin;
1	1	Staff Meeting		uploads/memos/692078a525ed2_1763735717.docx	application/vnd.openxmlformats-officedocument.wordprocessingml.document	single	1	f	active	2025-11-21 14:35:17.275472
2	1	Checks		uploads/memos/69207eae12100_1763737262.png	image/png	single	1	f	active	2025-11-21 15:01:04.424012
3	1	Checks		uploads/memos/69207ec7b9f02_1763737287.png	image/png	single	1	f	active	2025-11-21 15:01:29.889084
4	1	Checks		uploads/memos/69207f06cb2c4_1763737350.png	image/png	single	1	f	active	2025-11-21 15:02:30.953853
\.


--
-- Data for Name: pre_users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.pre_users (id, surname, firstname, othername, staff_id, salary_structure, gl, step, rank, is_registered, uploaded_at) FROM stdin;
1	Dike	Victor		FPOG/REG/PF/034				Senior Lecturer	t	2025-11-21 12:21:10.397292
2	Allison	Divine	Telema	FPOG/REG/PF/033				Senior Lecturer	f	2025-11-21 13:35:22.908901
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.sessions (id, user_id, user_type, session_token, ip_address, user_agent, created_at, expires_at) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, username, email, password, firstname, lastname, role, profile_picture, created_at, updated_at) FROM stdin;
1	superadmin	superadmin@fpog.edu.ng	$2y$10$gu.z2U/xuu.oZtIBRqX66.djgbxaLg9i1WOl9ElUJrGnCLA.aqK0.	Super	Admin	superadmin	uploads/profile_pictures/692073da396a3_1696772062543.jpg	2025-11-20 14:31:08.631889	2025-11-20 14:31:08.631889
\.


--
-- Name: admin_roles_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.admin_roles_id_seq', 3, true);


--
-- Name: admin_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.admin_users_id_seq', 1, true);


--
-- Name: memo_recipients_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.memo_recipients_id_seq', 4, true);


--
-- Name: memos_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.memos_id_seq', 4, true);


--
-- Name: pre_users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.pre_users_id_seq', 2, true);


--
-- Name: sessions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.sessions_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 3, true);


--
-- Name: admin_roles admin_roles_admin_id_role_name_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_roles
    ADD CONSTRAINT admin_roles_admin_id_role_name_key UNIQUE (admin_id, role_name);


--
-- Name: admin_roles admin_roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_roles
    ADD CONSTRAINT admin_roles_pkey PRIMARY KEY (id);


--
-- Name: admin_users admin_users_official_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_users
    ADD CONSTRAINT admin_users_official_email_key UNIQUE (official_email);


--
-- Name: admin_users admin_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_users
    ADD CONSTRAINT admin_users_pkey PRIMARY KEY (id);


--
-- Name: admin_users admin_users_staff_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_users
    ADD CONSTRAINT admin_users_staff_id_key UNIQUE (staff_id);


--
-- Name: memo_recipients memo_recipients_memo_id_admin_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memo_recipients
    ADD CONSTRAINT memo_recipients_memo_id_admin_id_key UNIQUE (memo_id, admin_id);


--
-- Name: memo_recipients memo_recipients_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memo_recipients
    ADD CONSTRAINT memo_recipients_pkey PRIMARY KEY (id);


--
-- Name: memos memos_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memos
    ADD CONSTRAINT memos_pkey PRIMARY KEY (id);


--
-- Name: pre_users pre_users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pre_users
    ADD CONSTRAINT pre_users_pkey PRIMARY KEY (id);


--
-- Name: pre_users pre_users_staff_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.pre_users
    ADD CONSTRAINT pre_users_staff_id_key UNIQUE (staff_id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_session_token_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_session_token_key UNIQUE (session_token);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_key UNIQUE (username);


--
-- Name: admin_roles admin_roles_admin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_roles
    ADD CONSTRAINT admin_roles_admin_id_fkey FOREIGN KEY (admin_id) REFERENCES public.admin_users(id) ON DELETE CASCADE;


--
-- Name: admin_roles admin_roles_assigned_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_roles
    ADD CONSTRAINT admin_roles_assigned_by_fkey FOREIGN KEY (assigned_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: admin_users admin_users_staff_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.admin_users
    ADD CONSTRAINT admin_users_staff_id_fkey FOREIGN KEY (staff_id) REFERENCES public.pre_users(staff_id) ON DELETE RESTRICT;


--
-- Name: memo_recipients memo_recipients_admin_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memo_recipients
    ADD CONSTRAINT memo_recipients_admin_id_fkey FOREIGN KEY (admin_id) REFERENCES public.admin_users(id) ON DELETE CASCADE;


--
-- Name: memo_recipients memo_recipients_memo_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memo_recipients
    ADD CONSTRAINT memo_recipients_memo_id_fkey FOREIGN KEY (memo_id) REFERENCES public.memos(id) ON DELETE CASCADE;


--
-- Name: memos memos_recipient_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memos
    ADD CONSTRAINT memos_recipient_id_fkey FOREIGN KEY (recipient_id) REFERENCES public.admin_users(id) ON DELETE SET NULL;


--
-- Name: memos memos_sender_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.memos
    ADD CONSTRAINT memos_sender_id_fkey FOREIGN KEY (sender_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict KPrLwnCGRSBa9Bifn4AagUAV5YbnfQaVU2IqX3IOaCMn8tMUgmx4wnHGppqGElJ

