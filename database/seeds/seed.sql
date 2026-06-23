-- =============================================================================
-- IMedia Registration — Sample Data (optional)
-- Import AFTER schema.sql. Safe to re-run.
-- =============================================================================

-- Sample registrations: one of each status
INSERT INTO `registrations`
  (`name`, `mobile`, `email`, `address`, `course`, `start_date`, `end_date`,
   `status`, `payment_status`, `paid_amount`, `paid_at`, `remark`)
VALUES
  ('Elena Marquez',  '+1 (555) 012-3456', 'elena.m@example.com',
   '742 Evergreen Terrace, Springfield, IL 62704', 'Advanced UX Design',
   '2026-10-12', '2027-05-15', 'confirm', 'fully_paid',
   1200.00, '2026-09-30', 'Full payment received via bank transfer.'),

  ('Marcus Thorne',  '+1 (555) 987-6543', 'm.thorne@devstudio.co',
   '1050 Market St, San Francisco, CA', 'Backend Architecture',
   '2026-11-01', '2027-05-01', 'confirm', 'deposit',
   600.00, '2026-10-15', '50% deposit paid; balance due before start.'),

  ('Sarah Tilson',   '+1 (555) 444-1234', 'sarah@cloud.com',
   '88 King St, San Francisco, CA 94107', 'Data Visualization',
   '2026-11-15', '2027-05-20', 'tentative', 'pending',
   NULL, NULL, NULL),

  ('Lila Vance',     '+1 (555) 222-9988', 'lila.v@uxco.me',
   '21 Jump Street, Seattle, WA 98101', 'Interactive Design',
   '2026-12-10', '2027-06-10', 'reschedule', 'deposit',
   500.00, '2026-11-01', 'Rescheduled to March batch per client request.'),

  ('James Rodriguez','+1 (555) 678-4321', 'j.rod@techmail.com',
   '500 Broadway, New York, NY 10012', 'Machine Learning',
   '2026-12-01', '2027-08-30', 'forfeit', 'pending',
   NULL, NULL, NULL),

  ('Ayesha Khan',    '+1 (555) 333-7777', 'ayesha.k@design.io',
   '1200 Elm Drive, Austin, TX 73301', 'Advanced UX Design',
   '2026-10-12', '2027-05-15', 'pending', 'pending',
   NULL, NULL, NULL);

-- Sample contact inquiry
INSERT INTO `contacts`
  (`name`, `mobile`, `email`, `subject`, `message`, `status`)
VALUES
  ('John Doe',  '09123456789', 'john.doe@example.com',
   'Inquiry about Corporate Training',
   'Hello, we are looking for a customized training program for our 15 engineers.',
   'pending'),
  ('Jane Smith', '09987654321', 'janesmith@gmail.com',
   'Question about schedule',
   'Can I take the backend course only on weekends?',
   'contacted');

-- Sample OJT application
INSERT INTO `applications`
  (`type`, `name`, `mobile`, `email`, `position`, `message`, `status`)
VALUES
  ('ojt', 'Mark Johnson', '09112223344', 'mark.j.student@university.edu',
   'Frontend Developer Intern',
   'I am a 4th-year student looking for a 300-hour internship program.',
   'pending');

-- Sample Trainer application
INSERT INTO `applications`
  (`type`, `name`, `mobile`, `email`, `position`, `message`, `status`)
VALUES
  ('trainer', 'Dr. Robert Chen', '09223334455', 'robert.chen@techacademy.com',
   'Senior Backend Instructor',
   'I have 10 years of industry experience and 5 years of teaching experience in Node.js and Python.',
   'accepted');
