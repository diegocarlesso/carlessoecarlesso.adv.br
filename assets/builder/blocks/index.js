/**
 * blocks/index.js — Registry de blocos disponíveis no Builder v1.
 *
 * Adicionar um bloco novo é: import + push em ALL_BLOCKS. O Sidebar lista
 * automaticamente, o Renderer encontra via `BLOCK_REGISTRY[type]`, o
 * Inspector chama `block.inspect(node, onChange)`.
 *
 * Os tipos batem com os widgets do PageRenderer (Phase 2 backend) — assim
 * o que é editado aqui é renderizado igual no site público sem conversão.
 */

import { SectionBlock }    from './section.js';
import { ColumnBlock }     from './column.js';
import { HeadingBlock }    from './heading.js';
import { TextBlock }       from './text.js';
import { TeamMemberBlock } from './team.js';

export const ALL_BLOCKS = [
  SectionBlock,
  ColumnBlock,
  HeadingBlock,
  TextBlock,
  TeamMemberBlock,
];

/** type → definition (read-only) */
export const BLOCK_REGISTRY = Object.freeze(
  ALL_BLOCKS.reduce((acc, b) => { acc[b.type] = b; return acc; }, {})
);

/** Lista pra Sidebar agrupada por categoria */
export function blocksByCategory() {
  const groups = {};
  for (const b of ALL_BLOCKS) {
    const cat = b.category || 'outros';
    (groups[cat] ||= []).push(b);
  }
  return groups;
}
