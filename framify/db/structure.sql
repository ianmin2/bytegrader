--- USERS


DROP TABLE IF EXISTS  dbo.users;
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

DROP TABLE IF EXISTS  dbo.assignments;
CREATE TABLE [dbo].[assignments](
	[assignment_id] [bigint] IDENTITY(1,1) NOT NULL,
	[assignment_name] [nvarchar](255) NOT NULL,
	[assignment_owner] [bigint] NOT NULL,
	[assignment_due] [datetimeoffset](7) NOT NULL,
	[assignment_summary] [nvarchar](max) NULL,
	[assignment_notes] [nvarchar](max) NULL,
	[assignment_created] [datetimeoffset](7) NOT NULL,
	[assignment_last_modified] [datetimeoffset](7),
	CONSTRAINT valid_assignment_owner_required
    FOREIGN KEY (assignment_owner)
    REFERENCES users (id)
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY];


ALTER TABLE [dbo].[assignments] ADD PRIMARY KEY CLUSTERED 
(
	[assignment_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

CREATE UNIQUE NONCLUSTERED INDEX MitigateAssignmentDuplicates ON assignments
(
        assignment_name,
        assignment_owner,
		assignment_due
) WITH( IGNORE_DUP_KEY = OFF);

ALTER TABLE [dbo].[assignments] ADD  DEFAULT (getdate()) FOR [assignment_created];

--- #####################################################################################################################

--- ROUTES

DROP TABLE IF EXISTS  dbo.routes;
CREATE TABLE [dbo].[routes](
	[rule_id] [bigint] IDENTITY(1,1) NOT NULL,
	[rule_method] [nvarchar](255) NOT NULL,
	[rule_path] [nvarchar](max) NOT NULL,
	[rule_name] [nvarchar](255) NOT NULL,
	[rule_description] [nvarchar](max) NOT NULL,
	[rule_assignment] [bigint] NOT NULL,
	[rule_expected_status_code] [int] NOT NULL,
	[rule_expected_data_type] [nvarchar](255) NOT NULL,
	[rule_expected_data] [nvarchar](max) NULL,
	[rule_headers] [nvarchar](max) NULL,
	[rule_parameters] [nvarchar](max) NULL,
	[rule_grading] [text] NOT NULL,
	[created_at] [datetimeoffset](7) NULL,
	[updated_at] [datetimeoffset](7) NULL,
	CONSTRAINT valid_route_assignment_required
    FOREIGN KEY (rule_assignment)
    REFERENCES assignments (assignment_id)
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

DROP TABLE IF EXISTS  dbo.chainings;
CREATE TABLE [dbo].[chainings](
	[chaining_id] [bigint] IDENTITY(1,1) NOT NULL,
	[chaining_assignment] [bigint] NOT NULL,
	[chaining_depends_on] [bigint] NULL,
	[chaining_parent] [bigint] NULL,
	[chaining_type] [nvarchar](255) NOT NULL,
	[chaining_rules] [text]NOT NULL,
	[created_at] [datetime] NULL,
	[updated_at] [datetime] NULL,
	CONSTRAINT valid_assignment_required
    FOREIGN KEY (chaining_assignment)
    REFERENCES assignments (assignment_id),
	CONSTRAINT valid_parent_assignment_required
    FOREIGN KEY (chaining_depends_on)
    REFERENCES assignments (assignment_id)
) ON [PRIMARY];

ALTER TABLE [dbo].[chainings] ADD PRIMARY KEY CLUSTERED 
(
	[chaining_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

ALTER TABLE [dbo].[chainings] ADD DEFAULT  GETDATE() FOR [created_at];

--- #####################################################################################################################

--- ATTEMPTS
DROP TABLE IF EXISTS  dbo.attempts;
CREATE TABLE [dbo].[attempts](
	[attempt_id] [bigint] IDENTITY(1,1) NOT NULL,
	[attempt_name] [nvarchar](255) NOT NULL,
	[attempt_student_identifier] [nvarchar](255) NOT NULL,
	[attempt_main_path] [nvarchar](255) NOT NULL,
	[attempt_submission_time] [nvarchar](255) NULL,
	[attempt_grading_time] [nvarchar](255) NULL,
	[attempt_grade] [nvarchar](255) NULL,
	[attempt_grade_breakdown] [text] NULL,
	[attempt_grade_complete] [bit] NULL,
	[attempt_assignment] [bigint] NOT NULL,
	[created_at] [datetime] NULL,
	[updated_at] [datetime] NULL,
	CONSTRAINT valid_attempt_assignment_required
    FOREIGN KEY (attempt_assignment)
    REFERENCES assignments (assignment_id)
) ON [PRIMARY];

ALTER TABLE [dbo].[attempts] ADD PRIMARY KEY CLUSTERED 
(
	[attempt_id] ASC
)WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY];

ALTER TABLE [dbo].[attempts] ADD  DEFAULT ('0') FOR [attempt_grade_complete];
ALTER TABLE [dbo].[attempts] ADD DEFAULT  GETDATE() FOR [attempt_submission_time];
ALTER TABLE [dbo].[attempts] ADD DEFAULT  GETDATE() FOR [created_at];


--- #####################################################################################################################

--- ERRORS

DROP TABLE IF EXISTS  dbo.errors;
CREATE TABLE [dbo].[errors](
	[error_id] [bigint] IDENTITY(1,1) NOT NULL,
	[error_assignment] [bigint] NOT NULL,
	[error_chaining] [bigint] NULL,
	[error_message] [text] NULL,
	[created_at] [datetime] NULL DEFAULT GETDATE(),
	[updated_at] [datetime] NULL,
	CONSTRAINT valid_error_assignment_required FOREIGN KEY (error_assignment) REFERENCES assignments (assignment_id),
	CONSTRAINT valid_error_chaining_required FOREIGN KEY (error_chaining) REFERENCES chainings (chaining_id)
) ON [PRIMARY];