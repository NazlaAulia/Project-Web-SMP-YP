// supabase-config.js
import { createClient } from 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js/+esm'

const supabaseUrl = 'https://mwqqflnzeoxcbqigoxhz.supabase.co'
const supabaseKey = 'sb_publishable_qgOE5u-TOYQnYPGf6tgi2Q_GmjsMEF4' 

export const supabase = createClient(supabaseUrl, supabaseKey)