--- USERS

CREATE TABLE [dbo].[users](
	[id] [bigint] IDENTITY(1,1) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[email] [nvarchar](255) NOT NULL,
	[email_verified_at] [datetime] NULL,
	[username] [nvarchar](50) NOT NULL,
	[password] [nvarchar](255) NOT NULL,
	[remember_token] [nvarchar](100) NULL,
	[user_active] [bit] DEFAULT 1,
	[user_last_seen] [datetime] DEFAULT GETDATE(),
	[created_at] [datetime] NULL,
	[updated_at] [datetime] NULL
) ON [PRIMARY];


ALTER TABLE [dbo].[users] ADD PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];


CREATE UNIQUE NONCLUSTERED INDEX [user_email_unique] ON [dbo].[users]
(
	[email] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];


ALTER TABLE [dbo].[users] ADD DEFAULT  GETDATE() FOR [created_at];

--- #####################################################################################################################

--- ASSIGNMENTS

CREATE TABLE [dbo].[assignments](
	[assignment_id] [bigint] IDENTITY(1,1) NOT NULL,
	[assignment_name] [nvarchar](255) NOT NULL,
	[assignment_owner] [bigint] NOT NULL,
	[assignment_due] [datetimeoffset](7) NOT NULL,
	[assignment_summary] [nvarchar](max) NULL,
	[assignment_notes] [nvarchar](max) NULL,
	[assignment_created] [datetimeoffset](7) NOT NULL,
	[assignment_last_modified] [datetimeoffset](7) NOT NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY];


ALTER TABLE [dbo].[assignments] ADD PRIMARY KEY CLUSTERED 
(
	[assignment_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];


ALTER TABLE [dbo].[assignments] ADD  DEFAULT (getdate()) FOR [assignment_created];

--- #####################################################################################################################

--- ROUTES

CREATE TABLE [dbo].[routes](
	[rule_id] [bigint] IDENTITY(1,1) NOT NULL,
	[rule_method] [nvarchar](255) NOT NULL,
	[rule_path] [nvarchar](max) NOT NULL,
	[rule_name] [nvarchar](255) NOT NULL,
	[rule_description] [nvarchar](255) NOT NULL,
	[rule_assignment] [bigint] NOT NULL,
	[rule_expected_status_code] [int] NOT NULL,
	[rule_expected_data_type] [nvarchar](255) NOT NULL,
	[rule_expected_data] [nvarchar](max) NULL,
	[rule_headers] [nvarchar](max) NULL,
	[rule_parameters] [nvarchar](max) NULL,
	[rule_grading] [nvarchar](max) NOT NULL,
	[created_at] [datetimeoffset](7) NULL,
	[updated_at] [datetimeoffset](7) NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY];

ALTER TABLE [dbo].[routes] ADD PRIMARY KEY CLUSTERED 
(
	[rule_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

ALTER TABLE [dbo].[routes] ADD  DEFAULT ('GET') FOR [rule_method];
ALTER TABLE [dbo].[routes] ADD  DEFAULT ('200') FOR [rule_expected_status_code];
ALTER TABLE [dbo].[routes] ADD  DEFAULT ('text/html') FOR [rule_expected_data_type];
ALTER TABLE [dbo].[routes] ADD DEFAULT  GETDATE() FOR [created_at];

--- #####################################################################################################################

---  CHAININGS

CREATE TABLE [dbo].[chainings](
	[id] [bigint] IDENTITY(1,1) NOT NULL,
	[chaining_assignment] [bigint] NOT NULL,
	[chaining_depends_on] [bigint] NULL,
	[chaining_type] [nvarchar](255) NOT NULL,
	[chaining_rules] [nvarchar](255) NOT NULL,
	[created_at] [datetime] NULL,
	[updated_at] [datetime] NULL
) ON [PRIMARY];

ALTER TABLE [dbo].[chainings] ADD PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

ALTER TABLE [dbo].[chainings] ADD DEFAULT  GETDATE() FOR [created_at];

--- #####################################################################################################################

--- ATTEMPTS

CREATE TABLE [dbo].[attempts](
	[attempt_id] [bigint] IDENTITY(1,1) NOT NULL,
	[attempt_name] [nvarchar](255) NOT NULL,
	[attempt_student_identifier] [nvarchar](255) NOT NULL,
	[attempt_main_path] [nvarchar](255) NOT NULL,
	[attempt_submission_time] [nvarchar](255) NULL,
	[attempt_grading_time] [nvarchar](255) NULL,
	[attempt_grade_breakdown] [nvarchar](255) NULL,
	[attempt_grade_complete] [bit] NULL,
	[attempt_assignment] [nvarchar](255) NOT NULL,
	[created_at] [datetime] NULL,
	[updated_at] [datetime] NULL
) ON [PRIMARY];

ALTER TABLE [dbo].[attempts] ADD PRIMARY KEY CLUSTERED 
(
	[attempt_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

ALTER TABLE [dbo].[attempts] ADD  DEFAULT ('0') FOR [attempt_grade_complete];
ALTER TABLE [dbo].[attempts] ADD DEFAULT  GETDATE() FOR [attempt_submission_time];
ALTER TABLE [dbo].[attempts] ADD DEFAULT  GETDATE() FOR [created_at];