CREATE TABLE public.grades (
  id uuid NOT NULL DEFAULT gen_random_uuid(),
  system_id text NOT NULL,
  student_name text NOT NULL,
  quiz numeric NOT NULL,
  laboratory numeric NOT NULL,
  assignment numeric NOT NULL,
  attendance numeric NOT NULL,
  major_exam numeric NOT NULL,
  final_grade numeric NOT NULL,
  created_at timestamp without time zone DEFAULT now(),
  CONSTRAINT grades_pkey PRIMARY KEY (id)
);